<?php
header('Content-Type: text/plain');
foreach ($_SERVER as $key => $value) {
    if (stripos($key, 'HTTP_') === 0 || stripos($key, 'SERVER_') === 0 || $key === 'HTTPS' || stripos($key, 'REMOTE') === 0) {
        echo "$key = $value\n";
    }
}
