<?php

// Include all the other scripts.

define('START_TIME', microtime(true));
define('ROOT', dirname(dirname(__FILE__)));

require ROOT . '/vendor/autoload.php';
require ROOT . '/includes/config.php';
require ROOT . '/includes/reddit-client.php';
require ROOT . '/includes/error-handler.php';
require ROOT . '/includes/recaptcha.php';
require ROOT . '/includes/database.php';
require ROOT . '/includes/functions.php';
