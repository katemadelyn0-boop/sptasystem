<?php
spl_autoload_register(function($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base   = __DIR__ . '/phpmailer/phpmailer/src/';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $file = $base . substr($class, strlen($prefix)) . '.php';
        if (file_exists($file)) require $file;
    }
});