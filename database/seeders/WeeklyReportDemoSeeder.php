<?php

namespace Database\Seeders;

use App\Models\AdministrativeWeeklyPeriod;
use App\Models\AdministrativeWeeklyReport;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WeeklyReportDemoSeeder extends Seeder
{
    public function run(): void
    {
        $staffRole = Role::query()->where('code', 'staff')->firstOrFail();
        $admin = User::query()->whereHas('role', fn ($role) => $role->where('code', 'admin'))->first();
        $period = AdministrativeWeeklyPeriod::query()->latest('week_start')->first();

        if (! $period) {
            $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $period = AdministrativeWeeklyPeriod::query()->create([
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                'due_date' => $weekStart->copy()->addDays(2)->toDateString(),
                'title' => 'Báo cáo tuần '.$weekStart->isoWeek().'/'.$weekStart->isoWeekYear(),
                'is_active' => true,
                'created_by' => $admin?->id,
                'activated_by' => $admin?->id,
                'activated_at' => now(),
            ]);
        }

        DB::transaction(function () use ($staffRole, $period): void {
            $users = collect(range(1, 10))->map(function (int $number) use ($staffRole): User {
                $email = sprintf('demo-report-%02d@demo.local', $number);
                $user = User::withTrashed()->firstOrNew(['email' => $email]);
                if ($user->trashed()) $user->restore();
                $attributes = [
                    'role_id' => $staffRole->id,
                    'name' => sprintf('Nhân viên báo cáo mẫu %02d', $number),
                    'active' => true,
                    'must_change_password' => true,
                    'notifications_enabled' => false,
                ];
                if (! $user->exists) $attributes['password'] = Str::random(48);
                $user->forceFill($attributes)->save();

                return $user;
            });

            $period->assignedUsers()->syncWithoutDetaching($users->pluck('id'));

            foreach ($users as $index => $user) {
                $number = $index + 1;
                $score = 84 + $number;
                $report = AdministrativeWeeklyReport::query()->updateOrCreate(
                    ['user_id' => $user->id, 'period_id' => $period->id],
                    [
                        'week_start' => $period->week_start->toDateString(),
                        'week_end' => $period->week_end->toDateString(),
                        'due_date' => $period->due_date->toDateString(),
                        'status' => 'submitted',
                        'quality_score' => $score,
                        'review_payload' => ['item_count' => 4, 'demo' => true],
                        'submitted_at' => now()->subMinutes(70 - ($number * 5)),
                    ]
                );

                $report->items()->delete();
                foreach ($this->items($number) as $sortOrder => $item) {
                    $plainText = trim(strip_tags(str_replace(['</li>', '<br>'], ["\n", "\n"], $item['content'])));
                    $report->items()->create($item + [
                        'normalized_content' => Str::lower($plainText),
                        'quality_score' => $score,
                        'review_payload' => ['passed' => true, 'score' => $score, 'demo' => true],
                        'sort_order' => $sortOrder,
                    ]);
                }
            }
        });

        $this->command?->info('Đã tạo 10 tài khoản mẫu và 10 báo cáo đã gửi cho tuần '.$period->week_start->format('d/m/Y').'.');
    }

    private function items(int $number): array
    {
        $areas = ['consulting_care', 'academic_affairs', 'teaching', 'other'];
        $area = $areas[($number - 1) % count($areas)];

        return [
            [
                'type' => 'results',
                'work_area' => $area,
                'content' => '<ul><li>Hoàn thành xử lý '.(12 + $number).' hồ sơ học viên và cập nhật dữ liệu trước 16:30 thứ Ba.</li><li>Phối hợp xác nhận '.(8 + $number).' trường hợp, đã bàn giao kết quả cho bộ phận phụ trách.</li></ul>',
            ],
            [
                'type' => 'other_work',
                'work_area' => 'other',
                'content' => '<p>Hỗ trợ sắp xếp tài liệu và kiểm tra phòng làm việc phục vụ hoạt động cuối tuần.</p>',
            ],
            [
                'type' => 'proposals',
                'work_area' => $area,
                'content' => '<p>Đề xuất bộ phận phụ trách cung cấp danh sách cập nhật trước 10:00 thứ Hai để đối chiếu dữ liệu đúng hạn.</p>',
            ],
            [
                'type' => 'next_plan',
                'work_area' => $area,
                'content' => '<ul><li>Hoàn thành rà soát '.(15 + $number).' hồ sơ còn lại trước thứ Tư.</li><li>Tổng hợp kết quả và gửi người phụ trách trước 16:00 thứ Sáu.</li></ul>',
            ],
        ];
    }
}
