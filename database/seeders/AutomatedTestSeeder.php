<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AutomatedTestSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::getDatabaseName() !== 'esky_automated_test') {
            throw new RuntimeException('AutomatedTestSeeder chỉ được chạy trên database esky_automated_test.');
        }

        DB::transaction(function (): void {
            DB::table('activity_logs')->delete();
            DB::table('login_logs')->delete();
            DB::table('users')->update([
                'password' => Hash::make('ChangeMe123!'),
                'must_change_password' => true,
                'zalo_id' => null,
                'zalo_name' => null,
                'zalo_linked_at' => null,
                'last_login_at' => null,
                'last_login_ip' => null,
                'remember_token' => null,
            ]);
            DB::statement("UPDATE users SET name=CONCAT('Tài khoản kiểm thử ', id), email=CONCAT('user', id, '@example.test')");
            DB::statement("UPDATE personnels SET code=CONCAT('NV', LPAD(id, 4, '0')), name=CONCAT('Nhân sự kiểm thử ', id), normalized_name=CONCAT('nhan su kiem thu ', id), email=CONCAT('personnel', id, '@example.test'), phone=CONCAT('090000', LPAD(id, 4, '0')), note=NULL");
            DB::statement("UPDATE language_students SET code=CONCAT('HV', LPAD(id, 4, '0')), name=CONCAT('Học viên kiểm thử ', id), date_of_birth=NULL, phone=CONCAT('091000', LPAD(id, 4, '0')), email=CONCAT('student', id, '@example.test'), address='Địa chỉ kiểm thử', school='Trường kiểm thử', source=NULL, note=NULL");
            DB::statement("UPDATE language_leads SET name=CONCAT('Khách hàng kiểm thử ', id), date_of_birth=NULL, phone=CONCAT('092000', LPAD(id, 4, '0')), email=CONCAT('lead', id, '@example.test'), zalo=NULL, source=NULL, consultation=NULL, note=NULL");
            DB::statement("UPDATE language_guardians SET name=CONCAT('Phụ huynh kiểm thử ', id), phone=CONCAT('093000', LPAD(id, 4, '0')), email=CONCAT('guardian', id, '@example.test'), zalo=NULL");
            DB::statement("UPDATE language_collaborators SET code=CONCAT('CTV', LPAD(id, 4, '0')), name=CONCAT('Cộng tác viên kiểm thử ', id), phone=CONCAT('094000', LPAD(id, 4, '0')), email=CONCAT('collaborator', id, '@example.test'), address='Địa chỉ kiểm thử', note=NULL");
            DB::statement("UPDATE language_target_submissions SET name=CONCAT('Khách hàng kiểm thử ', id), phone=CONCAT('095000', LPAD(id, 4, '0')), phone_normalized=CONCAT('095000', LPAD(id, 4, '0'))");
            DB::statement("UPDATE language_levels SET name=CONCAT('Cấp độ kiểm thử ', id), description=NULL");
            DB::statement("UPDATE language_tuition_payments SET receipt_code=IF(receipt_code IS NULL, NULL, CONCAT('PT-KT-', LPAD(id, 6, '0'))), reference=NULL, note=NULL");
            DB::statement("UPDATE kpi_records SET student_name=CONCAT('Học viên kiểm thử ', id), receipt_no=CONCAT('PT-KT-', LPAD(id, 6, '0')), note=NULL");
            DB::statement("UPDATE import_batches SET original_name=CONCAT('du-lieu-kiem-thu-', id, '.xlsx'), stored_path=CONCAT('private/imports/kiem-thu-', id, '.xlsx'), file_hash=SHA2(CONCAT('test-', id), 256), error_details=NULL");
            DB::statement("UPDATE upcoming_plans SET title=CONCAT('Kế hoạch kiểm thử ', id), note='Nội dung giả lập phục vụ kiểm thử'");
            DB::statement("UPDATE work_tasks SET title=CONCAT('Công việc kiểm thử ', id), description='Nội dung giả lập phục vụ kiểm thử'");
            DB::table('work_task_comments')->update(['body' => 'Phản hồi giả lập phục vụ kiểm thử']);
            DB::table('system_settings')->whereIn('key', [
                'bank_account_name', 'bank_account_number', 'bank_branch', 'footer_text',
            ])->delete();
            DB::table('system_settings')->updateOrInsert(['key' => 'bank_enabled'], ['value' => '0']);
        });
    }
}
