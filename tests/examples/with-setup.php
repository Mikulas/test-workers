<?php

echo "with-setup test executed\n";

$_SETUP = $_SETUP ?? require_once __DIR__ . '/setup.php';
var_dump($_SETUP);
