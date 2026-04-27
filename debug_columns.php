<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
use Illuminate\Support\Facades\DB;

$item = DB::connection('sqlsrv')->table('SiparisEkstralar')->first();
if ($item) {
    print_r(array_keys((array)$item));
} else {
    echo "No data";
}
