<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AutomatedTestSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::getDatabaseName() !== 'esky_automated_test') {
            throw new RuntimeException('AutomatedTestSeeder chỉ được chạy trên database esky_automated_test.');
        }

        DB::transaction(function (): void {
            DB::table('users')->update([
                'last_login_ip' => null,
                'remember_token' => null,
            ]);
            DB::statement("UPDATE users SET name=CONCAT('Tài khoản kiểm thử ', id), email=CONCAT('user', id, '@example.test')");
            DB::statement("UPDATE personnels SET name=CONCAT('Nhân sự kiểm thử ', id), normalized_name=CONCAT('nhan su kiem thu ', id), email=CONCAT('personnel', id, '@example.test'), phone=CONCAT('090000', LPAD(id, 4, '0')), note=NULL");
            DB::statement("UPDATE language_students SET name=CONCAT('Học viên kiểm thử ', id), phone=CONCAT('091000', LPAD(id, 4, '0')), email=CONCAT('student', id, '@example.test'), address='Địa chỉ kiểm thử', school='Trường kiểm thử', note=NULL");
            DB::statement("UPDATE language_leads SET name=CONCAT('Khách hàng kiểm thử ', id), phone=CONCAT('092000', LPAD(id, 4, '0')), email=CONCAT('lead', id, '@example.test'), zalo=NULL, consultation=NULL, note=NULL");
            DB::statement("UPDATE language_guardians SET name=CONCAT('Phụ huynh kiểm thử ', id), phone=CONCAT('093000', LPAD(id, 4, '0')), email=CONCAT('guardian', id, '@example.test'), zalo=NULL");
            DB::statement("UPDATE language_collaborators SET name=CONCAT('Cộng tác viên kiểm thử ', id), phone=CONCAT('094000', LPAD(id, 4, '0')), email=CONCAT('collaborator', id, '@example.test'), address='Địa chỉ kiểm thử', note=NULL");
            DB::statement("UPDATE upcoming_plans SET title=CONCAT('Kế hoạch kiểm thử ', id), note='Nội dung giả lập phục vụ kiểm thử'");
            DB::statement("UPDATE work_tasks SET title=CONCAT('Công việc kiểm thử ', id), description='Nội dung giả lập phục vụ kiểm thử'");
            DB::table('work_task_comments')->update(['body' => 'Phản hồi giả lập phục vụ kiểm thử']);
        });
    }
}
