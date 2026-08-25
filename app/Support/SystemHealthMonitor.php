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
            $add('Co so du lieu', 'Ket noi co so du lieu', true, 'Ket noi va truy van co so du lieu hoat dong.');
        } catch (Throwable) {
            $add('Co so du lieu', 'Ket noi co so du lieu', false, 'Khong the ket noi co so du lieu.', 'error');
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
                'Co so du lieu',
                'Cau truc bang nghiep vu',
                $missingTables->isEmpty(),
                $missingTables->isEmpty() ? 'Du '.count($requiredTables).' bang cot loi.' : 'Thieu bang: '.$missingTables->implode(', ')
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
                'Co so du lieu',
                'Migration chuc nang moi',
                $missingColumns->isEmpty(),
                $missingColumns->isEmpty() ? 'Du cot cho giao vu, kiem giang day, hoc phi, diem danh va so dau bai.' : 'Thieu cot: '.$missingColumns->implode(', ')
            );
        } catch (Throwable) {
            $add('Co so du lieu', 'Cau truc bang nghiep vu', false, 'Khong the doc cau truc bang do ket noi co so du lieu dang loi.');
            $add('Co so du lieu', 'Migration chuc nang moi', false, 'Khong the kiem tra cac cot chuc nang moi do ket noi co so du lieu dang loi.');
        }

        $requiredRoutes = [
            'language-students.index',
            'language-classes.index',
            'language-classes.show',
            'language-tuition.index',
            'language-tuition.monthly',
            'language-tuition.monthly.pdf',
            'language-tuition.outstanding-sheet',
            'teacher-classes.index',
            'teacher-classes.gradebook',
            'teacher-classes.teaching-load.index',
            'teacher-classes.teaching-load.pdf',
            'teacher-classes.attendance.store',
            'teacher-classes.lesson-book.store',
            'teacher-classes.lesson-book.print',
            'director.dashboard',
            'admin.system-test',
            'admin.system-test.catalog',
            'admin.trash.index',
            'admin.trash.restore',
        ];
        $missingRoutes = collect($requiredRoutes)->reject(fn (string $name) => Route::has($name))->values();
        $add(
            'Dieu huong',
            'Route nghiep vu trong yeu',
            $missingRoutes->isEmpty(),
            $missingRoutes->isEmpty() ? 'Du '.count($requiredRoutes).' route trong yeu.' : 'Thieu route: '.$missingRoutes->implode(', ')
        );

        $teacherRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => str_starts_with((string) $route->getName(), 'teacher-classes.'));
        $teacherRoutesWithoutPermission = $teacherRoutes->filter(fn (LaravelRoute $route) => ! collect($route->gatherMiddleware())
            ->contains(fn ($middleware) => str_starts_with((string) $middleware, 'permission:')));
        $add(
            'Phan quyen',
            'Bao ve lop giang day',
            $teacherRoutes->isNotEmpty() && $teacherRoutesWithoutPermission->isEmpty(),
            $teacherRoutesWithoutPermission->isEmpty()
                ? $teacherRoutes->count().' route giao vien deu co middleware quyen.'
                : 'Thieu quyen: '.$teacherRoutesWithoutPermission->map(fn (LaravelRoute $route) => $route->getName())->filter()->implode(', ')
        );

        $zipReady = extension_loaded('zip') && class_exists('ZipArchive');
        $add(
            'Moi truong PHP',
            'Doc va nhap Excel XLSX',
            $zipReady,
            $zipReady ? 'PHP ZipArchive da san sang.' : 'Thieu PHP ZipArchive; tep XLSX va mot so bao cao Excel se bi gioi han. Cac man co ho tro CSV van dung duoc, nhung nen bat extension=zip trong php.ini de dung day du.',
            'error'
        );

        $sqliteReady = extension_loaded('pdo_sqlite');
        $add(
            'Moi truong kiem thu',
            'Co so du lieu SQLite co lap',
            $sqliteReady,
            $sqliteReady ? 'PDO SQLite da san sang cho kiem thu giao dich co lap.' : 'Thieu PDO SQLite; nhom kiem thu thao tac cong viec se bi bo qua.',
            'warning'
        );

        $storagePaths = [storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')];
        $unwritable = collect($storagePaths)->reject(fn (string $path) => is_dir($path) && is_writable($path))->values();
        $add(
            'Moi truong PHP',
            'Quyen ghi thu muc Laravel',
            $unwritable->isEmpty(),
            $unwritable->isEmpty() ? 'Storage, log va cache co quyen ghi.' : 'Khong the ghi: '.$unwritable->implode(', ')
        );

        $themeFile = public_path('css/theme.css');
        $themeCss = is_file($themeFile) ? (string) file_get_contents($themeFile) : '';
        $themeTokens = ['--primary:', '--primary-rgb:', '--primary-soft:', '--primary-dark:', '.btn-primary', '.btn-outline-primary', '.text-primary', '.progress-bar'];
        $missingThemeTokens = collect($themeTokens)->reject(fn (string $token) => str_contains($themeCss, $token))->values();
        $loginView = resource_path('views/auth/login.blade.php');
        if (! is_file($loginView) || ! str_contains((string) file_get_contents($loginView), "asset('css/theme.css')")) {
            $missingThemeTokens->push('theme.css tren trang dang nhap');
        }
        $add(
            'Giao dien',
            'Dong bo mau cau hinh',
            $missingThemeTokens->isEmpty(),
            $missingThemeTokens->isEmpty() ? 'Nut, chu, nen, tien trinh va trang thai chinh dung bien mau cau hinh.' : 'Thieu anh xa CSS: '.$missingThemeTokens->implode(', ')
        );

        $layoutView = resource_path('views/layouts/app.blade.php');
        $layoutSupportsPageStyles = is_file($layoutView)
            && str_contains((string) file_get_contents($layoutView), "@stack('styles')");
        $add(
            'Giao dien',
            'Nap CSS rieng cua tung trang',
            $layoutSupportsPageStyles,
            $layoutSupportsPageStyles ? 'Layout chinh da render stack styles cua cac trang con.' : 'Layout chinh thieu @stack styles; CSS rieng cua trang se khong hoat dong.'
        );

        $requiredViews = [
            resource_path('views/language/classes/gradebook.blade.php'),
            resource_path('views/language/classes/print-lesson-book.blade.php'),
            resource_path('views/language/classes/show.blade.php'),
            resource_path('views/admin/system-test.blade.php'),
            resource_path('views/admin/trash.blade.php'),
            resource_path('views/director/dashboard.blade.php'),
            resource_path('views/language/tuition/monthly-pdf.blade.php'),
            resource_path('views/kpis/teaching-report-pdf.blade.php'),
        ];
        $missingViews = collect($requiredViews)->reject(fn (string $path) => is_file($path))->map(fn (string $path) => basename($path))->values();
        $add(
            'Giao dien',
            'View trong yeu',
            $missingViews->isEmpty(),
            $missingViews->isEmpty() ? 'Cac trang quan tri, thung rac, giam sat, so lop, ban in hoc phi va bao cao tiet day deu ton tai.' : 'Thieu view: '.$missingViews->implode(', ')
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
