<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Quản lý chỉ tiêu KPI.');
})->purpose('Hiển thị thông điệp hệ thống');
