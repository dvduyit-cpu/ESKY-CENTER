<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdministrativeReportAiCompiler
{
    public function __construct(private readonly OpenAiSettings $settings)
    {
    }

    private const HEADINGS = [
        '1. Công tác thực hiện (căn cứ theo nhiệm vụ được giao trong phân công công việc)',
        '2. Công tác khác',
        '3. Đề xuất, kiến nghị',
        '4. Kế hoạch trong tuần tới',
    ];

    private const OFFICIAL_HEADINGS = [
        '1. Công tác tư vấn – chăm sóc',
        '2. Công tác giáo vụ',
        '3. Công tác giảng dạy',
        '4. Công tác khác',
    ];

    /** @param Collection<int, object> $items */
    public function compile(Collection $items): ?string
    {
        $apiKey = $this->settings->apiKey();
        if (! $this->settings->enabled() || $apiKey === '' || $items->isEmpty()) return null;

        $source = $items->values()->map(function ($item, int $index): string {
            $text = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags((string) $item->content), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            return ($index + 1).'. ['.(string) $item->type.'] '.$text;
        })->filter()->implode("\n");

        $instructions = <<<'PROMPT'
Vai trò: Biên tập viên báo cáo hành chính bằng tiếng Việt.

Mục tiêu: Đọc toàn bộ ý báo cáo, sửa lỗi chính tả nhẹ, tách các đầu việc, loại bỏ ý trùng và phân loại đúng nghĩa vào đúng 4 mục bắt buộc.

Yêu cầu:
- Không ghi tên người báo cáo.
- Không sáng tác số liệu, kết quả, thời gian hay công việc mới.
- Giữ nguyên thông tin thực tế quan trọng.
- Mỗi đầu việc là một dòng bắt đầu bằng "- ".
- Nếu một câu chứa nhiều đầu việc, tách thành nhiều dòng.
- Ý đã hoàn thành đưa vào mục 1; việc phát sinh hỗ trợ đưa vào mục 2; đề nghị/khó khăn đưa vào mục 3; việc dự kiến đưa vào mục 4.
- Với các ý giống hoặc gần trùng: bắt buộc chỉ xuất hiện đúng 1 lần trong toàn bộ báo cáo, giữ bản rõ ràng và đầy đủ nhất; tuyệt đối không lặp lại ở cùng mục hoặc mục khác.
- Nếu mục không có dữ liệu, ghi "- Không có nội dung."
- Chỉ trả về văn bản thuần, không dùng bảng, không dùng khối mã, không có lời dẫn.

Đầu ra phải có đúng 4 tiêu đề, đúng thứ tự và đúng nguyên văn:
1. Công tác thực hiện (căn cứ theo nhiệm vụ được giao trong phân công công việc)
2. Công tác khác
3. Đề xuất, kiến nghị
4. Kế hoạch trong tuần tới
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout($this->settings->timeout())
                ->retry(1, 300)
                ->post((string) config('ai.openai.endpoint'), [
                    'model' => $this->settings->model(),
                    'reasoning' => ['effort' => 'low'],
                    'input' => [
                        ['role' => 'developer', 'content' => [['type' => 'input_text', 'text' => $instructions]]],
                        ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $source]]],
                    ],
                    'text' => ['verbosity' => 'low'],
                    'max_output_tokens' => 6000,
                ]);
            if (! $response->successful()) {
                Log::warning('OpenAI weekly report compilation failed.', ['status' => $response->status()]);
                return null;
            }

            $payload = $response->json();
            $content = trim((string) ($payload['output_text'] ?? collect($payload['output'] ?? [])
                ->flatMap(fn (array $output) => $output['content'] ?? [])
                ->firstWhere('type', 'output_text')['text'] ?? ''));

            if ($content === '' || collect(self::HEADINGS)->contains(fn (string $heading) => ! str_contains($content, $heading))) {
                Log::warning('OpenAI weekly report compilation returned an invalid format.');
                return null;
            }

            return $content;
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    /** @param Collection<int, object> $items */
    public function compileOfficial(Collection $items): ?string
    {
        $apiKey = $this->settings->apiKey();
        if (! $this->settings->enabled() || $apiKey === '' || $items->isEmpty()) return null;

        $source = $items->values()->map(function ($item, int $index): string {
            $text = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags((string) $item->content), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            return ($index + 1).'. [nhóm hiện tại: '.(string) ($item->work_area ?? 'other').'] '.$text;
        })->filter()->implode("\n");

        $instructions = <<<'PROMPT'
Vai trò: Biên tập viên báo cáo chính thức bằng tiếng Việt.

Mục tiêu: Kiểm tra nội dung, sửa lỗi chính tả nhẹ, tách đầu việc, loại ý trùng và tự phân loại theo bản chất công việc vào đúng 4 nhóm bắt buộc. Nhãn "nhóm hiện tại" chỉ là gợi ý; phải đọc nội dung để sửa nhóm nếu bị phân loại sai.

Yêu cầu:
- Không ghi tên người báo cáo.
- Không sáng tác hoặc làm thay đổi số liệu, kết quả, thời gian và nội dung thực tế.
- Mỗi đầu việc là một dòng bắt đầu bằng "- ".
- Một câu có nhiều đầu việc phải tách thành nhiều dòng.
- Nội dung giống hoặc gần trùng bắt buộc chỉ xuất hiện đúng 1 lần trong toàn bộ báo cáo; giữ bản rõ ràng, đầy đủ nhất.
- Tư vấn, tuyển sinh, liên hệ, hỗ trợ và chăm sóc học viên/phụ huynh đưa vào mục 1.
- Hồ sơ, lớp học, lịch học, điểm danh, học phí và nghiệp vụ giáo vụ đưa vào mục 2.
- Soạn giảng, đứng lớp, chấm bài và hoạt động chuyên môn giảng dạy đưa vào mục 3.
- Nội dung không thuộc ba nhóm trên đưa vào mục 4.
- Mục không có dữ liệu ghi "- Không có nội dung."
- Chỉ trả về văn bản thuần, không có lời dẫn, bảng hoặc khối mã.

Đầu ra phải có đúng 4 tiêu đề, đúng thứ tự và đúng nguyên văn:
1. Công tác tư vấn – chăm sóc
2. Công tác giáo vụ
3. Công tác giảng dạy
4. Công tác khác
PROMPT;

        return $this->requestCompilation($apiKey, $instructions, $source, self::OFFICIAL_HEADINGS, 'official');
    }

    private function requestCompilation(string $apiKey, string $instructions, string $source, array $headings, string $kind): ?string
    {
        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout($this->settings->timeout())
                ->retry(1, 300)
                ->post((string) config('ai.openai.endpoint'), [
                    'model' => $this->settings->model(),
                    'reasoning' => ['effort' => 'low'],
                    'input' => [
                        ['role' => 'developer', 'content' => [['type' => 'input_text', 'text' => $instructions]]],
                        ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $source]]],
                    ],
                    'text' => ['verbosity' => 'low'],
                    'max_output_tokens' => 6000,
                ]);
            if (! $response->successful()) {
                Log::warning('OpenAI weekly report compilation failed.', ['status' => $response->status(), 'kind' => $kind]);
                return null;
            }

            $payload = $response->json();
            $content = trim((string) ($payload['output_text'] ?? collect($payload['output'] ?? [])
                ->flatMap(fn (array $output) => $output['content'] ?? [])
                ->firstWhere('type', 'output_text')['text'] ?? ''));

            if ($content === '' || collect($headings)->contains(fn (string $heading) => ! str_contains($content, $heading))) {
                Log::warning('OpenAI weekly report compilation returned an invalid format.', ['kind' => $kind]);
                return null;
            }

            return $content;
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }
}
