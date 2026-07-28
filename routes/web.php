<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', ProductController::class);
    Route::post('products/{product}/add-stock', [ProductController::class, 'addStock'])->name('products.add-stock');
    Route::get('products/stock-report/download', [ProductController::class, 'downloadStockReport'])->name('products.stock-report');
    Route::get('clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::resource('clients', ClientController::class);
    Route::get('invoices/{invoice}/download', [App\Http\Controllers\InvoiceController::class, 'download'])->name('invoices.download');
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class)->except(['edit', 'update']);
});

Route::get('/export-live-db', function () {
    $tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
    $dbName = env('DB_DATABASE');
    $tableKey = 'Tables_in_' . $dbName;

    $sql = "-- Fresh Live Production Backup of {$dbName}\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET NAMES utf8mb4;\n\n";

    foreach ($tables as $t) {
        if (!isset($t->$tableKey)) {
            $props = get_object_vars($t);
            $tableName = reset($props);
        } else {
            $tableName = $t->$tableKey;
        }

        $create = Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$tableName}`");
        $createTableSql = $create[0]->{'Create Table'};
        $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
        $sql .= $createTableSql . ";\n\n";

        $rows = Illuminate\Support\Facades\DB::table($tableName)->get();
        if ($rows->count() > 0) {
            foreach ($rows->chunk(50) as $chunk) {
                $cols = array_keys((array)$chunk[0]);
                $colStr = "`" . implode("`,`", $cols) . "`";
                $sql .= "INSERT INTO `{$tableName}` ({$colStr}) VALUES\n";
                $valRows = [];
                foreach ($chunk as $r) {
                    $rArr = (array)$r;
                    $vals = array_map(function($val) {
                        if ($val === null) return 'NULL';
                        return Illuminate\Support\Facades\DB::getPdo()->quote($val);
                    }, $rArr);
                    $valRows[] = "(" . implode(",", $vals) . ")";
                }
                $sql .= implode(",\n", $valRows) . ";\n\n";
            }
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    return response($sql, 200, [
        'Content-Type' => 'text/plain',
        'Content-Disposition' => 'attachment; filename="backup.sql"',
    ]);
});

require __DIR__.'/auth.php';
