<?php

namespace App\Support;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemHealthMonitor
{
    public function checks(): array
    {
        $checks = [];
        $add = function (string $category, string $name, bool $passed, string $detail, string $severity = 'error') use (&$checks): void {
            $checks[] = compact('category', 'name', 'passed', 'detail', 'severity');
        };

        try {
            DB::connection()->getPdo();
            $add('Cơ sở dữ liệu', 'Kết nối cơ sở dữ liệu', true, 'Kết nối và truy vấn cơ sở dữ liệu hoạt động.');
        } catch (Throwable) {
            $add('Cơ sở dữ liệu', 'Kết nối cơ sở dữ liệu', false, 'Không thể kết nối cơ sở dữ liệu.', 'error');
        }

        try {
            $requiredTables = [
                'users', 'roles', 'modules', 'role_permissions', 'user_permissions',
                'language_students', 'language_classes', 'language_enrollments',
                'language_tuition_charges', 'language_tuition_payments',
                'language_class_lessons', 'language_class_attendances',
            ];
            $missingTables = collect($requiredTables)->reject(fn (string $table) => Schema::hasTable($table))->values();
            $add(
                'Cơ sở dữ liệu',
                'Cấu trúc bảng nghiệp vụ',
                $missingTables->isEmpty(),
                $missingTables->isEmpty() ? 'Đủ '.count($requiredTables).' bảng cốt lõi.' : 'Thiếu bảng: '.$missingTables->implode(', ')
            );

            $requiredColumns = [
                'users' => ['is_registrar', 'is_instructor', 'theme_color'],
                'language_classes' => ['teacher_user_id', 'expected_sessions', 'completed_sessions', 'completion_requested_at'],
                'language_tuition_charges' => ['paid_amount', 'credit_amount', 'status'],
                'language_class_lessons' => ['lesson_date', 'content', 'teacher_signature', 'attendance_marked_at'],
            ];
            $missingColumns = collect($requiredColumns)->flatMap(function (array $columns, string $table) {
                if (! Schema::hasTable($table)) {
                    return collect([$table.'.*']);
                }

                return collect($columns)
                    ->reject(fn (string $column) => Schema::hasColumn($table, $column))
                    ->map(fn (string $column) => $table.'.'.$column);
            })->values();
            $add(
                'Cơ sở dữ liệu',
                'Migration chức năng mới',
                $missingColumns->isEmpty(),
                $missingColumns->isEmpty() ? 'Đủ cột cho giáo vụ, kiêm giảng dạy, học phí, điểm danh và sổ đầu bài.' : 'Thiếu cột: '.$missingColumns->implode(', ')
            );
        } catch (Throwable) {
            $add('Cơ sở dữ liệu', 'Cấu trúc bảng nghiệp vụ', false, 'Không thể đọc cấu trúc bảng do kết nối cơ sở dữ liệu đang lỗi.');
            $add('Cơ sở dữ liệu', 'Migration chức năng mới', false, 'Không thể kiểm tra các cột chức năng mới do kết nối cơ sở dữ liệu đang lỗi.');
        }

        $requiredRoutes = [
            'language-students.index', 'language-classes.index', 'language-tuition.index',
            'teacher-classes.index', 'teacher-classes.gradebook', 'teacher-classes.attendance.store',
            'teacher-classes.lesson-book.store', 'teacher-classes.lesson-book.print',
            'director.dashboard', 'admin.system-test', 'admin.system-test.catalog',
        ];
        $missingRoutes = collect($requiredRoutes)->reject(fn (string $name) => Route::has($name))->values();
        $add(
            'Điều hướng',
            'Route nghiệp vụ trọng yếu',
            $missingRoutes->isEmpty(),
            $missingRoutes->isEmpty() ? 'Đủ '.count($requiredRoutes).' route trọng yếu.' : 'Thiếu route: '.$missingRoutes->implode(', ')
        );

        $teacherRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => str_starts_with((string) $route->getName(), 'teacher-classes.'));
        $teacherRoutesWithoutPermission = $teacherRoutes->filter(fn (LaravelRoute $route) => ! collect($route->gatherMiddleware())
            ->contains(fn ($middleware) => str_starts_with((string) $middleware, 'permission:')));
        $add(
            'Phân quyền',
            'Bảo vệ lớp giảng dạy',
            $teacherRoutes->isNotEmpty() && $teacherRoutesWithoutPermission->isEmpty(),
            $teacherRoutesWithoutPermission->isEmpty()
                ? $teacherRoutes->count().' route giáo viên đều có middleware quyền.'
                : 'Thiếu quyền: '.$teacherRoutesWithoutPermission->map(fn (LaravelRoute $route) => $route->getName())->filter()->implode(', ')
        );

        $zipReady = extension_loaded('zip') && class_exists('ZipArchive');
        $add(
            'Môi trường PHP',
            'Đọc và nhập Excel XLSX',
            $zipReady,
            $zipReady ? 'PHP ZipArchive đã sẵn sàng.' : 'Thiếu PHP ZipArchive; tệp XLSX và một số báo cáo Excel sẽ bị giới hạn. Các màn có hỗ trợ CSV vẫn dùng được, nhưng nên bật extension=zip trong php.ini để dùng đầy đủ.',
            'error'
        );

        $sqliteReady = extension_loaded('pdo_sqlite');
        $add(
            'Môi trường kiểm thử',
            'Cơ sở dữ liệu SQLite cô lập',
            $sqliteReady,
            $sqliteReady ? 'PDO SQLite đã sẵn sàng cho kiểm thử giao dịch cô lập.' : 'Thiếu PDO SQLite; nhóm kiểm thử thao tác công việc sẽ bị bỏ qua.',
            'warning'
        );

        $storagePaths = [storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')];
        $unwritable = collect($storagePaths)->reject(fn (string $path) => is_dir($path) && is_writable($path))->values();
        $add(
            'Môi trường PHP',
            'Quyền ghi thư mục Laravel',
            $unwritable->isEmpty(),
            $unwritable->isEmpty() ? 'Storage, log và cache có quyền ghi.' : 'Không thể ghi: '.$unwritable->implode(', ')
        );

        $themeFile = public_path('css/theme.css');
        $themeCss = is_file($themeFile) ? (string) file_get_contents($themeFile) : '';
        $themeTokens = ['--primary:', '--primary-rgb:', '--primary-soft:', '--primary-dark:', '.btn-primary', '.btn-outline-primary', '.text-primary', '.progress-bar'];
        $missingThemeTokens = collect($themeTokens)->reject(fn (string $token) => str_contains($themeCss, $token))->values();
        $loginView = resource_path('views/auth/login.blade.php');
        if (! is_file($loginView) || ! str_contains((string) file_get_contents($loginView), "asset('css/theme.css')")) {
            $missingThemeTokens->push('theme.css trên trang đăng nhập');
        }
        $add(
            'Giao diện',
            'Đồng bộ màu cấu hình',
            $missingThemeTokens->isEmpty(),
            $missingThemeTokens->isEmpty() ? 'Nút, chữ, nền, tiến trình và trạng thái chính dùng biến màu cấu hình.' : 'Thiếu ánh xạ CSS: '.$missingThemeTokens->implode(', ')
        );

        $layoutView = resource_path('views/layouts/app.blade.php');
        $layoutSupportsPageStyles = is_file($layoutView)
            && str_contains((string) file_get_contents($layoutView), "@stack('styles')");
        $add(
            'Giao diện',
            'Nạp CSS riêng của từng trang',
            $layoutSupportsPageStyles,
            $layoutSupportsPageStyles ? 'Layout chính đã render stack styles của các trang con.' : 'Layout chính thiếu @stack styles; CSS riêng của trang sẽ không hoạt động.'
        );

        $requiredViews = [
            resource_path('views/language/classes/gradebook.blade.php'),
            resource_path('views/language/classes/print-lesson-book.blade.php'),
            resource_path('views/admin/system-test.blade.php'),
            resource_path('views/director/dashboard.blade.php'),
        ];
        $missingViews = collect($requiredViews)->reject(fn (string $path) => is_file($path))->map(fn (string $path) => basename($path))->values();
        $add(
            'Giao diện',
            'View trọng yếu',
            $missingViews->isEmpty(),
            $missingViews->isEmpty() ? 'Các trang quản trị, giám sát, sổ lớp và bản in đều tồn tại.' : 'Thiếu view: '.$missingViews->implode(', ')
        );

        return $checks;
    }

    public function summary(?array $checks = null): array
    {
        $checks ??= $this->checks();
        $failed = collect($checks)->where('passed', false);
        $errors = $failed->where('severity', 'error')->count();
        $warnings = $failed->where('severity', 'warning')->count();

        return [
            'status' => $errors > 0 ? 'critical' : ($warnings > 0 ? 'warning' : 'healthy'),
            'total' => count($checks),
            'passed' => collect($checks)->where('passed', true)->count(),
            'errors' => $errors,
            'warnings' => $warnings,
            'issues' => $failed->values()->all(),
            'checked_at' => now(),
        ];
    }
}
