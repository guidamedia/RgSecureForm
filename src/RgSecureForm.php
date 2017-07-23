<?php

namespace RobGuida\RgSecureForm;
use Exception;

/**
 * Class RgSecureForm
 * @package RobGuida\RgSecureForm
 */
class RgSecureForm
{
    /**
     * @param int $life in seconds. 0 = indefinite an must be deleted manually
     * @return string
     * @throws Exception
     */
    public static function getToken($life = 0)
    {
        $form = new RgSecureForm();
        /* get a salt and a key to store the salt in the cache */
        $cache_key = uniqid(rand(1000, 9999));
        $cache_val = ['salt' => '', 'left' => '', 'center' => ''];
        $cache_val['salt'] = uniqid(rand(1000, 9999), true);
        /* to make sure the token is not predictable we need to add some random strings to the token */
        $cache_val['left'] = $form->getRandomString();
        $cache_val['center'] = $form->getRandomString();
        /* when a $life span is provided, use it, or store it until it is deleted */
        $result = apc_add($cache_key, $cache_val, $life);
        if (!$result) {
            throw new Exception('apc_add() failed to save the data');
        }
        /* generate the token to return */
        $token = $form->generateToken($cache_val['salt']);
        return "{$cache_val['left']}{$token}{$cache_val['center']}{$cache_key}";
    }

    /**
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public static function validateToken($token)
    {
        $form = new RgSecureForm();
        /* split the token to get the apc cache key and the token */
        $cache_key = substr($token, -17);
        if (apc_exists($cache_key)) {
            $cache_value = apc_fetch($cache_key);
            apc_delete($cache_key);
            $secret_to_compare = $form->generateToken($cache_value['salt']);
            unset($cache_value['salt']);
            $cache_value[] = $cache_key;
            $secret = str_replace(array_values($cache_value), '', $token);
            $output = ($secret == $secret_to_compare);
            if (!$output) {
                throw new Exception('The token is not valid');
            }
        } else {
            throw new Exception('The token either expired or was never set');
        }
        return $output;
    }

    /**
     * @param string $salt
     * @return string
     */
    private function generateToken($salt)
    {
        $REMOTE_ADDR = explode('.', $_SERVER['REMOTE_ADDR']);
        $SERVER_ADDR = explode('.', $_SERVER['SERVER_ADDR']);
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
