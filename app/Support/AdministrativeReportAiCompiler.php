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
        $source = $this->source($items, fn ($item) => '['.(string) $item->type.']');
        $instructions = <<<'PROMPT'
Vai trò: Biên tập viên báo cáo hành chính bằng tiếng Việt.

Mục tiêu: Đọc toàn bộ ý báo cáo, sửa lỗi chính tả nhẹ, tách các đầu việc và phân loại đúng nghĩa vào 4 mục bắt buộc.

Yêu cầu:
- Không ghi tên người báo cáo và không sáng tác số liệu, kết quả, thời gian hay công việc mới.
- Giữ nguyên đầy đủ chi tiết thực tế quan trọng.
- Mỗi đầu việc là một dòng bắt đầu bằng "- ".
- Không tự bỏ nội dung gần giống. Chỉ gộp khi hai ý hoàn toàn trùng về đầu việc, số liệu và kết quả.
- Ý hoàn thành vào mục 1; việc phát sinh hỗ trợ vào mục 2; đề nghị/khó khăn vào mục 3; việc dự kiến vào mục 4.
- Nếu mục không có dữ liệu, ghi "- Không có nội dung."
- Chỉ trả về văn bản thuần, không dùng bảng, khối mã hay lời dẫn.

Đầu ra phải có đúng 4 tiêu đề, theo đúng thứ tự và nguyên văn:
1. Công tác thực hiện (căn cứ theo nhiệm vụ được giao trong phân công công việc)
2. Công tác khác
3. Đề xuất, kiến nghị
4. Kế hoạch trong tuần tới
PROMPT;

        return $this->requestCompilation($instructions, $source, self::HEADINGS, 'summary');
    }

    /** @param Collection<int, object> $items */
    public function compileOfficial(Collection $items): ?string
    {
        $source = $this->source($items, fn ($item) => '[nhóm hiện tại: '.(string) ($item->work_area ?? 'other').']');
        $instructions = <<<'PROMPT'
Vai trò: Biên tập viên báo cáo chính thức bằng tiếng Việt.

Mục tiêu: Kiểm tra nội dung, sửa lỗi chính tả nhẹ, tách đầu việc và phân loại theo bản chất công việc vào đúng 4 nhóm. Nhãn "nhóm hiện tại" chỉ là gợi ý; hãy đọc nội dung để sửa nhóm nếu bị phân loại sai.

Yêu cầu:
- Không ghi tên người báo cáo và không sáng tác hay làm thay đổi số liệu, kết quả, thời gian và nội dung thực tế.
- Mỗi đầu việc là một dòng bắt đầu bằng "- ". Một câu có nhiều đầu việc phải tách thành nhiều dòng.
- Không tự bỏ nội dung gần giống. Chỉ gộp khi hai ý hoàn toàn trùng về đầu việc, số liệu và kết quả.
- Tư vấn, tuyển sinh, liên hệ, hỗ trợ và chăm sóc học viên/phụ huynh vào mục 1.
- Hồ sơ, lớp học, lịch học, điểm danh, học phí và nghiệp vụ giáo vụ vào mục 2.
- Soạn giảng, đứng lớp, chấm bài và hoạt động chuyên môn giảng dạy vào mục 3.
- Nội dung không thuộc ba nhóm trên vào mục 4.
- Mục không có dữ liệu ghi "- Không có nội dung."
- Chỉ trả về văn bản thuần, không có lời dẫn, bảng hoặc khối mã.

Đầu ra phải có đúng 4 tiêu đề, theo đúng thứ tự và nguyên văn:
1. Công tác tư vấn – chăm sóc
2. Công tác giáo vụ
3. Công tác giảng dạy
4. Công tác khác
PROMPT;

        return $this->requestCompilation($instructions, $source, self::OFFICIAL_HEADINGS, 'official');
    }

    /** @param Collection<int, object> $items */
    private function source(Collection $items, callable $prefix): string
    {
        return $items->values()->map(function ($item, int $index) use ($prefix): string {
            $text = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags((string) $item->content), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

            return ($index + 1).'. '.$prefix($item).' '.$text;
        })->filter()->implode("\n");
    }

    private function requestCompilation(string $instructions, string $source, array $headings, string $kind): ?string
    {
        $apiKey = $this->settings->apiKey();
        if (! $this->settings->enabled() || $apiKey === '' || $source === '') return null;

        try {
            $provider = $this->settings->provider();
            $response = match ($provider) {
                'gemini' => Http::acceptJson()
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->timeout($this->settings->timeout())
                    ->retry(1, 300)
                    ->post(rtrim((string) config('ai.gemini.endpoint'), '/').'/'.rawurlencode($this->settings->model()).':generateContent', [
                        'systemInstruction' => ['parts' => [['text' => $instructions]]],
                        'contents' => [['role' => 'user', 'parts' => [['text' => $source]]]],
                        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 6000],
                    ]),
                default => Http::withToken($apiKey)
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
                    ]),
            };

            if (! $response->successful()) {
                Log::warning('AI weekly report compilation failed.', ['provider' => $provider, 'status' => $response->status(), 'kind' => $kind]);

                return null;
            }

            $payload = $response->json();
            $content = $provider === 'gemini'
                ? collect(data_get($payload, 'candidates.0.content.parts', []))->pluck('text')->filter()->implode("\n")
                : (string) ($payload['output_text'] ?? collect($payload['output'] ?? [])
                    ->flatMap(fn (array $output) => $output['content'] ?? [])
                    ->firstWhere('type', 'output_text')['text'] ?? '');
            $content = trim($content);

            if ($content === '' || collect($headings)->contains(fn (string $heading) => ! str_contains($content, $heading))) {
                Log::warning('AI weekly report compilation returned an invalid format.', ['provider' => $provider, 'kind' => $kind]);

                return null;
            }

            return $content;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
