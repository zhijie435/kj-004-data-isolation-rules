<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => '在线课程教务系统',
        'version' => '1.0.0',
        'feature' => '数据隔离规则',
        'docs' => '/api/documentation',
    ]);
});
