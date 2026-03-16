<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'spta_system');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<h2 style="font-family:sans-serif;color:red;padding:20px;">Database connection failed: ' . $conn->connect_error . '<br>Please check config/db.php</h2>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
