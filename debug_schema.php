<?php
require 'c:/xampp/htdocs/dianora_siparis/vendor/autoload.php';
$app = require_once 'c:/xampp/htdocs/dianora_siparis/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("SELECT COLUMN_NAME, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ayar_gecmisi'");
print_r($cols);
