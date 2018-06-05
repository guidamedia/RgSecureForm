<?php

session_start();

echo(__FILE__ . ' ' . __LINE__ . ' $_SESSION:<pre>' . print_r($_SESSION, true) . '</pre>');
