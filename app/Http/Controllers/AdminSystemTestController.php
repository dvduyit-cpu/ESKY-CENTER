<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Throwable;

class AdminSystemTestController extends Controller
{
    private const MODULES = [
        ['group' => 'Tổng quan & công việc', 'name' => 'Tổng quan hệ thống', 'prefix' => 'dashboard', 'index' => 'dashboard'],
        ['group' => 'Tổng quan & công việc', 'name' => 'Trang chủ', 'prefix' => 'welcome', 'index' => 'welcome'],
        ['group' => 'Tổng quan & công việc', 'name' => 'Kế hoạch cá nhân', 'prefix' => 'plans', 'index' => 'plans.index'],
        ['group' => 'Tổng quan & công việc', 'name' => 'Giao task', 'prefix' => 'tasks', 'index' => 'tasks.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Tổng quan trung tâm', 'prefix' => 'language-dashboard', 'index' => 'language-dashboard.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Học viên tiềm năng', 'prefix' => 'language-leads', 'index' => 'language-leads.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Công việc tư vấn', 'prefix' => 'language-consulting', 'index' => 'language-consulting.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Gửi chỉ tiêu', 'prefix' => 'language-target-submissions', 'index' => 'language-target-submissions.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Cộng tác viên', 'prefix' => 'language-collaborators', 'index' => 'language-collaborators.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Học viên', 'prefix' => 'language-students', 'index' => 'language-students.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Thu học phí', 'prefix' => 'language-tuition', 'index' => 'language-tuition.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Lớp học', 'prefix' => 'language-classes', 'index' => 'language-classes.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Lớp giảng dạy & điểm', 'prefix' => 'teacher-classes', 'index' => 'teacher-classes.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Chương trình & cấp độ', 'prefix' => 'language-programs', 'index' => 'language-programs.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Khóa học trung tâm', 'prefix' => 'language-center-courses', 'index' => 'language-center-courses.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Chỉ tiêu trung tâm', 'prefix' => 'language-targets', 'index' => 'language-targets.index'],
        ['group' => 'Trung tâm ngoại ngữ', 'name' => 'Chế độ miễn giảm', 'prefix' => 'language-discounts', 'index' => 'language-discounts.index'],
        ['group' => 'KPI & báo cáo', 'name' => 'Tổng quan KPI', 'prefix' => 'kpi-dashboard', 'index' => 'kpi-dashboard.index'],
        ['group' => 'KPI & báo cáo', 'name' => 'Kế hoạch chỉ tiêu', 'prefix' => 'kpis', 'index' => 'kpis.index'],
        ['group' => 'KPI & báo cáo', 'name' => 'Khóa học & quy đổi', 'prefix' => 'courses', 'index' => 'courses.index'],
        ['group' => 'KPI & báo cáo', 'name' => 'Nhập kết quả Excel', 'prefix' => 'imports', 'index' => 'imports.index'],
        ['group' => 'KPI & báo cáo', 'name' => 'Báo cáo', 'prefix' => 'reports', 'index' => 'reports.index'],
        ['group' => 'KPI & báo cáo', 'name' => 'Thanh toán vượt', 'prefix' => 'payments', 'index' => 'payments.index'],
        ['group' => 'Quản trị', 'name' => 'Nhân sự & CTV', 'prefix' => 'personnels', 'index' => 'personnels.index'],
        ['group' => 'Quản trị', 'name' => 'Tài khoản', 'prefix' => 'users', 'index' => 'users.index'],
        ['group' => 'Quản trị', 'name' => 'Vai trò & quyền', 'prefix' => 'roles', 'index' => 'roles.index'],
        ['group' => 'Quản trị', 'name' => 'Nhật ký hệ thống', 'prefix' => 'logs', 'index' => 'logs.index'],
        ['group' => 'Quản trị', 'name' => 'Cấu hình phần mềm', 'prefix' => 'settings', 'index' => 'settings.edit', 'parameters' => ['section' => 'general']],
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Trang kiểm thử chỉ dành cho Admin.');

        return view('admin.system-test');
    }

    public function catalog(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Trang kiểm thử chỉ dành cho Admin.');

        $namedRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => $route->getName())
            ->keyBy(fn (LaravelRoute $route) => $route->getName());

        $modules = collect(self::MODULES)->map(function (array $module) use ($namedRoutes) {
            $route = $namedRoutes->get($module['index']);
            $prefixRoutes = $namedRoutes
                ->filter(fn (LaravelRoute $item, string $name) => $name === $module['prefix'] || str_starts_with($name, $module['prefix'].'.'));

            $operations = [];
            foreach ($prefixRoutes as $name => $item) {
                $methods = array_values(array_diff($item->methods(), ['HEAD']));
                $operations[] = [
                    'name' => $name,
                    'methods' => implode('|', $methods),
                    'action' => class_basename($item->getActionName()),
                    'has_parameters' => str_contains($item->uri(), '{'),
                    'middleware' => $item->gatherMiddleware(),
                ];
            }

            return [
                ...$module,
                'url' => $route ? route($module['index'], $module['parameters'] ?? []) : null,
                'route_ok' => (bool) $route,
                'operations' => $operations,
                'capabilities' => $this->capabilities($operations),
            ];
        });

        $database = ['ok' => true, 'message' => 'Kết nối cơ sở dữ liệu hoạt động'];
        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            report($exception);
            $database = ['ok' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu. Xem log hệ thống để biết chi tiết.'];
        }

        return response()->json([
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'database' => $database,
            'security_checks' => $this->securityChecks($namedRoutes),
            'modules' => $modules,
        ]);
    }

    private function securityChecks($namedRoutes): array
    {
        $checks = [];
        $add = function (string $name, bool $passed, string $detail, string $severity = 'error') use (&$checks): void {
            $checks[] = compact('name', 'passed', 'detail', 'severity');
        };

        $adminRoutes = $namedRoutes->filter(fn (LaravelRoute $route, string $name) => str_starts_with($name, 'admin.system-test'));
        $add(
            'Trang kiểm thử chỉ dành cho Admin',
            $adminRoutes->count() === 2,
            'Hai endpoint đều xác minh user có vai trò admin trong controller.'
        );

        $unprotected = $namedRoutes->filter(function (LaravelRoute $route, string $name) {
            if (in_array($name, ['login', 'login.submit'], true)) return false;
            return !in_array('auth', $route->gatherMiddleware(), true);
        });
        $add(
            'Xác thực người dùng trên route nội bộ',
            $unprotected->isEmpty(),
            $unprotected->isEmpty() ? 'Tất cả route nghiệp vụ yêu cầu đăng nhập.' : 'Route thiếu auth: '.$unprotected->keys()->implode(', ')
        );

        $mutations = $namedRoutes->filter(fn (LaravelRoute $route) => count(array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) > 0);
        $withoutWeb = $mutations->filter(fn (LaravelRoute $route) => !in_array('web', $route->gatherMiddleware(), true));
        $add(
            'CSRF cho thao tác ghi dữ liệu',
            $withoutWeb->isEmpty(),
            $withoutWeb->isEmpty() ? 'Các route ghi dữ liệu nằm trong nhóm web và được Laravel bảo vệ CSRF.' : 'Route ngoài nhóm web: '.$withoutWeb->keys()->implode(', ')
        );

        $sensitivePrefixes = ['users.', 'roles.', 'personnels.', 'logs.', 'settings.'];
        $missingPermission = $namedRoutes->filter(function (LaravelRoute $route, string $name) use ($sensitivePrefixes) {
            if (!collect($sensitivePrefixes)->contains(fn ($prefix) => str_starts_with($name, $prefix))) return false;
            return !collect($route->gatherMiddleware())->contains(fn ($middleware) => str_starts_with($middleware, 'permission:'));
        });
        $add(
            'Phân quyền các module quản trị',
            $missingPermission->isEmpty(),
            $missingPermission->isEmpty() ? 'Tài khoản, vai trò, nhân sự, log và cấu hình đều có middleware phân quyền.' : 'Thiếu permission: '.$missingPermission->keys()->implode(', ')
        );

        $production = app()->environment('production');
        $add(
            'Chế độ debug trên production',
            !$production || !config('app.debug'),
            $production
                ? (config('app.debug') ? 'APP_DEBUG đang bật: có nguy cơ lộ stack trace và thông tin cấu hình.' : 'APP_DEBUG đã tắt.')
                : 'Môi trường hiện tại không phải production; hãy bảo đảm APP_DEBUG=false khi upload host.',
            $production ? 'error' : 'warning'
        );
        $add(
            'Cookie phiên đăng nhập',
            (bool) config('session.http_only') && (!$production || (bool) config('session.secure')),
            'HttpOnly: '.(config('session.http_only') ? 'bật' : 'tắt').'; Secure: '.(config('session.secure') ? 'bật' : 'tắt').'.',
            $production ? 'error' : 'warning'
        );

        return $checks;
    }

    private function capabilities(array $operations): array
    {
        $names = collect($operations)->pluck('name');
        $methods = collect($operations)->pluck('methods')->implode('|');

        return [
            'view' => $names->contains(fn ($name) => str_ends_with($name, '.index') || in_array($name, ['dashboard', 'welcome'], true)),
            'search' => $names->contains(fn ($name) => str_ends_with($name, '.index')),
            'create' => str_contains($methods, 'POST'),
            'update' => str_contains($methods, 'PUT') || str_contains($methods, 'PATCH'),
            'delete' => str_contains($methods, 'DELETE'),
            'export' => $names->contains(fn ($name) => str_contains($name, 'export') || str_contains($name, 'template')),
        ];
    }
}
