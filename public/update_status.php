<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AdminOrderController;
use App\Core\Database\DB;

$config = require __DIR__ . '/../config/database.php';
DB::connect($config['dsn'], $config['user'], $config['pass']);

$controller = new AdminOrderController();
$controller->updateStatus();
