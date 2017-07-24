<?php

namespace RobGuida\RgSecureForm;
use Exception;

/**
 * Class RgSecureForm
 * @package RobGuida\RgSecureForm
 */
class RgSecureForm
{
    /** @var array  */
    private $allowable_servers = [];
    /** @var array */
    private $cache_value = ['salt' => '', 'left' => '', 'center' => '', 'server' => ''];
    /** @var string */
    private $server;

    /**
     * RgSecureForm constructor
     * @param array $allowable_servers
     */
    public function __construct(array $allowable_servers = [])
    {
        $this->allowable_servers = $allowable_servers;
        $this->server = $_SERVER['SERVER_ADDR'];
    }

    /**
     * @param $server
     */
    public function addAllowableServer($server)
    {
        $this->allowable_servers[] = $server;
    }

    /**
     * @return array
     */
    public function getAllowableServers()
    {
        return $this->allowable_servers;
    }

    /**
     * @param int $life in seconds. 0 = indefinite an must be deleted manually
     * @return string
     * @throws Exception
     */
    public static function getToken($life = 0)
    {
        $form = new RgSecureForm();
        return $form->genToken($life);
    }

    /**
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public static function validateToken($token)
    {
        $form = new RgSecureForm();
        $output = $form->verifyToken($token);
        return $output;
    }

    /**
     * @param int $life
     * @return string
     * @throws Exception
     */
    public function genToken($life = 0)
    {
        /* get a salt and a key to store the salt in the cache */
        $cache_key = uniqid(rand(1000, 9999));
        $this->cache_value['salt'] = uniqid(rand(1000, 9999), true);
        /* to make sure the token is not predictable we need to add some random strings to the token */
        $this->cache_value['left'] = $this->getRandomString();
        $this->cache_value['center'] = $this->getRandomString();
        /* if $allowable_servers is set, then we need to track the server from
            which the token is created.  Otherwise we want to a little of data as possible */
        if (in_array($this->server, $this->allowable_servers)) {
            $this->cache_value['server'] = $this->server;
        }
        /* when a $life span is provided, use it, or store it until it is deleted */
        $result = apc_add($cache_key, $this->cache_value, $life);
        if (!$result) {
            throw new Exception('apc_add() failed to save the data');
        }
        /* generate the token to return */
        $token = $this->generateToken($this->cache_value['salt']);
        return "{$this->cache_value['left']}{$token}{$this->cache_value['center']}{$cache_key}";
    }

    /**
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public function verifyToken($token)
    {
        $cache_key = substr($token, -17);
        /* if the key expires or was never set in the first place, a throw Exception */
        if (apc_exists($cache_key)) {
            /* get the array from the cache */
            $cache_value = apc_fetch($cache_key);
            /* delete the key, because they are only allowed to be used one time */
            apc_delete($cache_key);
            /* If the "server" key is set, then we need to make sure there are allowable servers set
                and if not, then there is an issue. The same method for getting the token needs to be done
                to validate the token. If a token is created from the static method, then the static method
                needs to be used to verify the token. */
            if (!empty($cache_value['sever'])) {
                /* If allowable servers are not set, when the server was set when generating the token,
                    then the wrong method is being used to verify the token */
                if (empty($this->allowable_servers)) {
                    throw new Exception('The same method for generating the key must be used to verify it');
                } elseif (!in_array($cache_value['server'], $this->allowable_servers)) {
                    throw new Exception('The server used to verify the token is unknown');
                } elseif ($this->server == $cache_value['server']) {
                    throw new Exception('The server used to create the token does not match the current server');
                }
            }
            $secret_to_compare = $this->generateToken($cache_value['salt'], $cache_value['server']);
            /* adjust the array so that we can use the "left", "center", and "server" and remove them from
                the token string. So, we need to remove the salt */
            unset($cache_value['salt']);
            $cache_value[] = $cache_key;
            $secret = str_replace(array_values($cache_value), '', $token);
            $output = ($secret == $secret_to_compare);
            /* if the token does not match, then it throws an Exception */
            if (!$output) {
                throw new Exception('The token is not valid');
            }
            return $output;
        } else {
            throw new Exception('The token either expired or was never set');
        }
    }

    /**
     * @param string $salt
     * @param string|null $server
     * @return string
     */
    private function generateToken($salt, $server = null)
    {
        if (empty($server)) {
            $server = $this->server;
        }
        $REMOTE_ADDR = explode('.', $_SERVER['REMOTE_ADDR']);
        $SERVER_ADDR = explode('.', $server);
        usort($REMOTE_ADDR, [$this, 'sortArray']);
        usort($SERVER_ADDR, [$this, 'sortArray']);
        $confused = implode('', array_merge($REMOTE_ADDR, $SERVER_ADDR));
        $token = "{$confused}_{$salt}";
        $output = md5($token);
        return $output;
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @return int
     */
    private function sortArray($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? -1 : 1;
    }

    /**
     * @return string
     */
    private function getRandomString()
    {
        $output = [];
        $max = rand(3, 10);
        for ($i = 0; $i < $max; $i++) {
            $type = rand(1, 3);
            if (1 == $type) {
                $output[] = chr(rand(65, 90));
            } elseif (2 == $type) {
                $output[] = chr(rand(97, 122));
            } else {
                $output[] = rand(1, 10);
            }
        }
        return implode('', $output);
    }
}
