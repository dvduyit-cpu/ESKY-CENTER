<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdministrativeReportReviewer
{
    private const VAGUE_PHRASES = [
        'đã xử lý', 'đang xử lý', 'đang thực hiện', 'đã thực hiện', 'một số',
        'các công việc', 'công việc khác', 'cơ bản', 'tương đối', 'ổn', 'sớm',
        'kịp thời', 'theo kế hoạch', 'như thường lệ', 'vân vân', 'v.v',
    ];

    private const STOP_WORDS = [
        'và', 'là', 'của', 'cho', 'đã', 'đang', 'sẽ', 'các', 'một', 'những',
        'trong', 'với', 'theo', 'được', 'tại', 'về', 'này', 'đó', 'để', 'từ',
    ];

    private const COMMON_MISSPELLINGS = [
        'ko' => 'không', 'k' => 'không', 'dc' => 'được', 'đc' => 'được',
        'sữ dụng' => 'sử dụng', 'xữ lý' => 'xử lý', 'sẳn sàng' => 'sẵn sàng',
        'bổ xung' => 'bổ sung', 'xát nhận' => 'xác nhận', 'xuất xắc' => 'xuất sắc',
        'chỉnh chu' => 'chỉn chu', 'sát nhập' => 'sáp nhập',
    ];

    public function review(string $content): array
    {
        $content = $this->plainText($content);
        $score = 100;
        $issues = [];
        $suggestions = [];

        if (mb_strlen($content) < 20) {
            $score -= 45;
            $issues[] = 'Nội dung quá ngắn, chưa thể hiện rõ việc gì đã làm hoặc sẽ làm.';
            $suggestions[] = 'Bổ sung đối tượng công việc, kết quả và thời hạn cụ thể.';
        }

        $matchedVague = collect(self::VAGUE_PHRASES)
            ->filter(fn (string $phrase) => Str::contains(mb_strtolower($content), $phrase))
            ->values();
        if ($matchedVague->isNotEmpty()) {
            $score -= min(50, 25 + ($matchedVague->count() * 10));
            $issues[] = 'Có từ ngữ chưa cụ thể: '.$matchedVague->implode(', ').'.';
            $suggestions[] = 'Thay bằng số lượng, tên đầu việc, kết quả hoặc mốc thời gian có thể kiểm chứng.';
        }

        $hasNumber = preg_match('/\d/u', $content) === 1;
        $hasDate = preg_match('/\b(?:\d{1,2}[\/\-.]\d{1,2}(?:[\/\-.]\d{2,4})?|thứ\s+[2-7]|tuần\s+\d+)\b/iu', $content) === 1;
        $hasResultLanguage = preg_match('/\b(?:hoàn thành|bàn giao|gửi|duyệt|tiếp nhận|liên hệ|cập nhật|xử lý|đạt|tăng|giảm|chốt|đối soát|hoàn tất)\b/iu', $content) === 1;
        if (! $hasNumber && ! $hasDate && ! $hasResultLanguage) {
            $score -= 25;
            $issues[] = 'Chưa thấy kết quả, số liệu hoặc mốc thời gian có thể kiểm chứng.';
            $suggestions[] = 'Nêu rõ đã làm bao nhiêu, cho ai, kết quả gì hoặc hoàn thành khi nào.';
        }

        if (preg_match('/\s{2,}|[,.!?;:]{2,}/u', $content) === 1) {
            $score -= 10;
            $issues[] = 'Có khoảng trắng hoặc dấu câu bị lặp.';
            $suggestions[] = 'Kiểm tra lại chính tả và dấu câu trước khi gửi.';
        }

        $misspellings = collect(self::COMMON_MISSPELLINGS)->filter(function (string $replacement, string $word) use ($content): bool {
            return preg_match('/(^|[^\p{L}])'.preg_quote($word, '/').'(?=$|[^\p{L}])/iu', $content) === 1;
        });
        if ($misspellings->isNotEmpty()) {
            $score -= min(30, $misspellings->count() * 10);
            $issues[] = 'Có từ viết tắt hoặc lỗi chính tả thường gặp: '.$misspellings->keys()->implode(', ').'.';
            $suggestions[] = 'Nên sửa thành: '.$misspellings->map(fn (string $correct, string $wrong) => $wrong.' → '.$correct)->implode('; ').'.';
        }

        if ($content !== '' && preg_match('/[.!?…]$/u', $content) !== 1) {
            $score -= 5;
            $issues[] = 'Câu chưa có dấu kết thúc.';
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'passed' => $score >= 60,
            'issues' => array_values(array_unique($issues)),
            'suggestions' => array_values(array_unique($suggestions)),
            'normalized' => $this->normalize($content),
        ];
    }

    public function normalize(string $content): string
    {
        $content = $this->plainText($content);
        $ascii = Str::ascii(mb_strtolower($content));
        $ascii = preg_replace('/[^a-z0-9\s]/', ' ', $ascii) ?? '';
        $tokens = preg_split('/\s+/', trim($ascii), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stopWords = array_map(fn (string $word) => Str::ascii($word), self::STOP_WORDS);

        return collect($tokens)
            ->reject(fn (string $token) => in_array($token, $stopWords, true))
            ->implode(' ');
    }

    /** @param Collection<int, object> $items Objects must expose id and content. */
    public function duplicateGroups(Collection $items, float $threshold = 0.62): array
    {
        $rows = $items->map(function ($item): array {
            $tokens = array_values(array_unique(preg_split('/\s+/', $this->normalize((string) $item->content), -1, PREG_SPLIT_NO_EMPTY) ?: []));
            return ['id' => (int) $item->id, 'tokens' => $tokens];
        })->filter(fn (array $row) => count($row['tokens']) >= 3)->values();

        $groups = [];
        for ($left = 0; $left < $rows->count(); $left++) {
            for ($right = $left + 1; $right < $rows->count(); $right++) {
                $a = $rows[$left];
                $b = $rows[$right];
                $intersection = count(array_intersect($a['tokens'], $b['tokens']));
                $union = count(array_unique(array_merge($a['tokens'], $b['tokens'])));
                $jaccardSimilarity = $union > 0 ? $intersection / $union : 0;
                $shorterTokenCount = min(count($a['tokens']), count($b['tokens']));
                $containmentSimilarity = $shorterTokenCount > 0 ? $intersection / $shorterTokenCount : 0;
                $similarity = max($jaccardSimilarity, $containmentSimilarity);
                if ($similarity < $threshold) continue;

                $matchingGroup = null;
                foreach ($groups as $index => $group) {
                    if (in_array($a['id'], $group['item_ids'], true) || in_array($b['id'], $group['item_ids'], true)) {
                        $matchingGroup = $index;
                        break;
                    }
                }
                if ($matchingGroup === null) {
                    $groups[] = ['item_ids' => [$a['id'], $b['id']], 'similarity' => round($similarity * 100)];
                } else {
                    $groups[$matchingGroup]['item_ids'] = array_values(array_unique(array_merge($groups[$matchingGroup]['item_ids'], [$a['id'], $b['id']])));
                    $groups[$matchingGroup]['similarity'] = max($groups[$matchingGroup]['similarity'], round($similarity * 100));
                }
            }
        }

        return array_values($groups);
    }

    /**
     * Giữ đúng một ý đại diện trong mỗi nhóm trùng, ưu tiên ý rõ ràng và đầy đủ hơn.
     *
     * @param Collection<int, object> $items Objects must expose id and content.
     * @return Collection<int, object>
     */
    public function deduplicate(Collection $items, float $threshold = 0.62): Collection
    {
        $discardedIds = collect();
        $groups = collect($this->duplicateGroups($items, $threshold));

        $exactGroups = $items->groupBy(fn ($item) => $this->normalize((string) $item->content))
            ->filter(fn (Collection $group, string $normalized) => $normalized !== '' && $group->count() > 1)
            ->map(fn (Collection $group) => ['item_ids' => $group->pluck('id')->map(fn ($id) => (int) $id)->all()]);

        $groups->concat($exactGroups)->each(function (array $group) use ($items, $discardedIds): void {
            $candidates = $items->whereIn('id', $group['item_ids'])
                ->reject(fn ($item) => $discardedIds->contains((int) $item->id));
            if ($candidates->count() < 2) return;

            $representative = $candidates->sort(function ($left, $right): int {
                $scoreComparison = ((int) ($right->quality_score ?? 0)) <=> ((int) ($left->quality_score ?? 0));
                if ($scoreComparison !== 0) return $scoreComparison;

                return mb_strlen($this->plainText((string) $right->content)) <=> mb_strlen($this->plainText((string) $left->content));
            })->first();

            $discardedIds->push(...$candidates->pluck('id')->map(fn ($id) => (int) $id)->reject(fn (int $id) => $id === (int) $representative->id));
        });

        return $items->reject(fn ($item) => $discardedIds->contains((int) $item->id))->values();
    }

    private function plainText(string $content): string
    {
        $content = preg_replace('/<(?:br\s*\/?|\/(?:p|div|li|blockquote|h[1-6]))>/i', ' ', $content) ?? $content;
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $content) ?? '');
    }
}
