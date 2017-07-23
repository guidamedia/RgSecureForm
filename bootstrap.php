<?php

error_reporting(E_ALL);
session_start([
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'cookie_httponly' => true,
    'session_switch' => true,
    'cookie_lifetime' => 54000, // 15 minutes
]);
session_regenerate_id(true);
date_default_timezone_set('America/New_York');
spl_autoload_register('autoLoader');

function autoLoader($class)
{
    static $autoLoader;
    if (is_null($autoLoader)) {
        $autoLoader = array();
    }
    if (!in_array($class, $autoLoader)) {
        $parts = explode("\\", $class);
        $class = end($parts);
        $file = __DIR__ . "/src/{$class}.php";
        if (file_exists("$file")) {
            require($file);
        } else {
            throw new Exception("The namespace for '{$class}' resolves to '{$file}', which does not exist!");
        }
    }
}
