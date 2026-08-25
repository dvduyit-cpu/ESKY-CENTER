<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class ProductionBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('INITIAL_ADMIN_EMAIL'));
        $password = (string) env('INITIAL_ADMIN_PASSWORD');
        $name = trim((string) env('INITIAL_ADMIN_NAME', 'Quản trị viên'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('INITIAL_ADMIN_EMAIL chưa phải là một địa chỉ email hợp lệ.');
        }
        if (Str::length($password) < 12) {
            throw new RuntimeException('INITIAL_ADMIN_PASSWORD phải có ít nhất 12 ký tự.');
        }
        if (User::withTrashed()->exists()) {
            throw new RuntimeException('Đã có tài khoản trong database. Seeder khởi tạo production đã dừng để bảo vệ dữ liệu.');
        }

        DB::transaction(function () use ($email, $password, $name): void {
            $this->recordImportedSchemaMigrations();

            $roles = collect([
                ['code' => 'admin', 'name' => 'Admin', 'description' => 'Quản trị toàn bộ hệ thống'],
                ['code' => 'leader', 'name' => 'Lãnh đạo', 'description' => 'Lãnh đạo và quản lý đơn vị'],
                ['code' => 'teacher', 'name' => 'Giáo viên', 'description' => 'Giáo viên phụ trách lớp học'],
                ['code' => 'staff', 'name' => 'Nhân viên', 'description' => 'Nhân viên sử dụng hệ thống'],
            ])->mapWithKeys(function (array $data): array {
                $role = Role::updateOrCreate(['code' => $data['code']], $data + ['is_system' => true]);

                return [$role->code => $role];
            });

            $modules = collect([
                ['administration', 'Hành chính - báo cáo & văn thư', 'bi-building-check', 5],
                ['system_dashboard', 'Tổng quan toàn hệ thống', 'bi-speedometer2', 1],
                ['kpi_dashboard_all', 'Tổng quan KPI toàn hệ thống', 'bi-graph-up', 2],
                ['language_dashboard_all', 'Tổng quan trung tâm toàn hệ thống', 'bi-building', 3],
                ['work_tasks', 'Công việc', 'bi-list-check', 4],
                ['personnel', 'Nhân sự & cộng tác viên', 'bi-people', 10],
                ['language_consulting', 'Công việc tư vấn', 'bi-headset', 18],
                ['language_target_submissions', 'Gửi chỉ tiêu', 'bi-send', 19],
                ['users', 'Tài khoản', 'bi-person-lock', 20],
                ['language_leads', 'Học viên tiềm năng', 'bi-person-plus', 20],
                ['language_students', 'Học viên', 'bi-mortarboard', 21],
                ['language_programs', 'Chương trình & cấp độ', 'bi-diagram-3', 22],
                ['language_classes', 'Lớp học', 'bi-door-open', 23],
                ['language_collaborators', 'Cộng tác viên trung tâm', 'bi-person-badge', 24],
                ['language_courses', 'Khóa học trung tâm', 'bi-journal-bookmark', 25],
                ['language_discounts', 'Chế độ miễn giảm', 'bi-percent', 26],
                ['language_tuition', 'Thu học phí', 'bi-cash-coin', 27],
                ['language_targets', 'Chỉ tiêu trung tâm theo tháng', 'bi-bullseye', 28],
                ['roles', 'Vai trò & quyền', 'bi-shield-lock', 30],
                ['teacher_classes', 'Lớp giảng dạy & điểm', 'bi-easel', 31],
                ['teaching_load_management', 'Tổng hợp giờ dạy', 'bi-kanban', 32],
                ['kpis', 'Chỉ tiêu KPI', 'bi-bullseye', 40],
                ['courses', 'Khóa học', 'bi-book', 50],
                ['imports', 'Nhập Excel', 'bi-file-earmark-arrow-up', 60],
                ['reports', 'Báo cáo', 'bi-bar-chart', 70],
                ['payments', 'Thanh toán vượt', 'bi-wallet2', 80],
                ['logs', 'Nhật ký hệ thống', 'bi-clock-history', 90],
                ['software_settings', 'Cấu hình phần mềm', 'bi-gear', 96],
            ])->mapWithKeys(function (array $data): array {
                [$code, $moduleName, $icon, $sortOrder] = $data;
                $module = Module::updateOrCreate(
                    ['code' => $code],
                    ['name' => $moduleName, 'icon' => $icon, 'sort_order' => $sortOrder]
                );

                return [$module->code => $module];
            });

            foreach ($roles as $roleCode => $role) {
                foreach ($modules as $moduleCode => $module) {
                    RolePermission::updateOrCreate(
                        ['role_id' => $role->id, 'module_id' => $module->id],
                        $this->permissionFor($roleCode, $moduleCode)
                    );
                }
            }

            DB::table('system_settings')->updateOrInsert(
                ['key' => 'theme_color'],
                ['value' => 'blue', 'created_at' => now(), 'updated_at' => now()]
            );

            $personnel = Personnel::create([
                'code' => 'ADM001',
                'name' => $name,
                'normalized_name' => Str::ascii(Str::lower($name)),
                'type' => 'admin',
                'position' => 'Admin hệ thống',
                'email' => $email,
                'default_kpi' => 0,
                'has_kpi' => false,
                'is_consultant' => false,
                'payment_type' => 'none',
                'payment_value' => 0,
                'active' => true,
            ]);

            User::create([
                'personnel_id' => $personnel->id,
                'role_id' => $roles['admin']->id,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'active' => true,
                'must_change_password' => true,
                'notifications_enabled' => true,
            ]);
        });
    }

    private function recordImportedSchemaMigrations(): void
    {
        if (! Schema::hasTable('migrations')) {
            throw new RuntimeException('Không tìm thấy bảng migrations. Hãy import DATABASE_ESKY_EMPTY.sql trước.');
        }

        $rows = collect(File::files(database_path('migrations')))
            ->sortBy(fn ($file) => $file->getFilename())
            ->values()
            ->map(fn ($file) => [
                'migration' => $file->getFilenameWithoutExtension(),
                'batch' => 1,
            ])
            ->all();

        DB::table('migrations')->insertOrIgnore($rows);
    }

    private function permissionFor(string $role, string $module): array
    {
        $none = ['can_view' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false, 'can_export' => false];
        $all = ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true, 'can_export' => true];

        if ($role === 'admin') {
            if ($module === 'work_tasks') {
                return array_replace($all, ['can_export' => false]);
            }
            if (in_array($module, ['teacher_classes', 'software_settings'], true)) {
                return array_replace($none, ['can_view' => true, 'can_update' => true]);
            }
            if ($module === 'teaching_load_management') {
                return array_replace($none, ['can_view' => true]);
            }

            return $all;
        }

        if ($role === 'leader') {
            if ($module === 'software_settings') {
                return $none;
            }
            if ($module === 'work_tasks') {
                return array_replace($all, ['can_export' => false]);
            }
            if ($module === 'teacher_classes') {
                return array_replace($none, ['can_view' => true, 'can_update' => true]);
            }
            if ($module === 'teaching_load_management') {
                return $none;
            }

            return $all;
        }

        if (in_array($role, ['teacher', 'staff'], true) && $module === 'administration') {
            return array_replace($none, ['can_view' => true, 'can_create' => true, 'can_update' => true]);
        }

        if ($role === 'teacher' && $module === 'teacher_classes') {
            return array_replace($none, ['can_view' => true, 'can_update' => true]);
        }

        if ($role === 'teacher' && in_array($module, ['work_tasks', 'language_target_submissions', 'reports'], true)) {
            return $all;
        }

        if ($role === 'staff' && $module === 'work_tasks') {
            return array_replace($none, ['can_view' => true, 'can_update' => true]);
        }

        return $none;
    }
}
