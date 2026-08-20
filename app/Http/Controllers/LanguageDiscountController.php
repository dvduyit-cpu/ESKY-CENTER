<?php

namespace App\Http\Controllers;

use App\Models\LanguageDiscountPolicy;
use App\Support\{CenterCode, ExcelExporter};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class LanguageDiscountController extends Controller
{
    public function index(Request $request): View
    {
        $query = LanguageDiscountPolicy::latest();

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%$search%")
                ->orWhere('eligible_subject', 'like', "%$search%"));
        }

        return view('language.discounts.index', [
            'items' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('language.discounts.form', ['item' => new LanguageDiscountPolicy]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->data($request);
        $data['code'] = CenterCode::next('language_discount_policies', 'MG', 2);

        LanguageDiscountPolicy::create($data);

        return redirect()->route('language-discounts.index')->with('success', 'Đã thêm chế độ miễn giảm.');
    }

    public function edit(LanguageDiscountPolicy $languageDiscount): View
    {
        return view('language.discounts.form', ['item' => $languageDiscount]);
    }

    public function update(Request $request, LanguageDiscountPolicy $languageDiscount): RedirectResponse
    {
        $languageDiscount->update($this->data($request));

        return redirect()->route('language-discounts.index')->with('success', 'Đã cập nhật chế độ miễn giảm.');
    }

    public function destroy(LanguageDiscountPolicy $languageDiscount): RedirectResponse
    {
        $languageDiscount->delete();

        return back()->with('success', 'Đã xóa chế độ miễn giảm.');
    }

    public function export()
    {
        return ExcelExporter::download(
            'che-do-mien-giam-'.date('Ymd').'.xlsx',
            ['Mã', 'Tên chế độ', 'Giảm %', 'Đối tượng', 'Từ ngày', 'Đến ngày', 'Trạng thái'],
            LanguageDiscountPolicy::withTrashed()->get()->map(fn ($item) => [
                $item->code,
                $item->name,
                $item->percentage,
                $item->eligible_subject,
                $item->starts_at?->format('d/m/Y'),
                $item->ends_at?->format('d/m/Y'),
                $item->active ? 'Hoạt động' : 'Ngừng',
            ])
        );
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'eligible_subject' => 'required|max:255',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'note' => 'nullable',
        ]);
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
