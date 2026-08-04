<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeDocument;
use App\Models\AdministrativeDocumentAttachment;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdministrativeDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = AdministrativeDocument::query()->with(['creator:id,name', 'attachments'])->latest('document_date')->latest('id');
        if ($request->filled('q')) {
            $keyword = trim($request->string('q')->toString());
            $query->where(fn ($row) => $row
                ->where('document_number', 'like', '%'.$keyword.'%')
                ->orWhere('document_symbol', 'like', '%'.$keyword.'%')
                ->orWhere('drafter', 'like', '%'.$keyword.'%')
                ->orWhere('signer', 'like', '%'.$keyword.'%')
                ->orWhere('summary', 'like', '%'.$keyword.'%')
                ->orWhere('destination', 'like', '%'.$keyword.'%')
                ->orWhere('receiver', 'like', '%'.$keyword.'%'));
        }
        if ($request->integer('year') >= 2000 && $request->integer('year') <= 2100) {
            $query->whereYear('document_date', $request->integer('year'));
        }

        return view('administration.documents.index', [
            'documents' => $query->paginate(Pagination::perPage())->withQueryString(),
            'years' => collect(range(now()->year + 1, now()->year - 10)),
            'canCreate' => $request->user()->allowed('administration', 'create'),
            'canUpdate' => $request->user()->allowed('administration', 'update'),
            'canDelete' => $request->user()->allowed('administration', 'delete'),
        ]);
    }

    public function create(): View
    {
        return view('administration.documents.form', ['document' => new AdministrativeDocument()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateDocument($request);
        $storedPaths = [];
        try {
            $document = DB::transaction(function () use ($request, $data, &$storedPaths): AdministrativeDocument {
                $document = AdministrativeDocument::query()->create($this->documentData($data) + [
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
                $this->storeAttachments($request, $document, $storedPaths);
                return $document;
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('administration.documents.edit', $document)->with('success', 'Đã lưu hồ sơ văn bản.');
    }

    public function edit(AdministrativeDocument $document): View
    {
        $document->load('attachments');
        return view('administration.documents.form', compact('document'));
    }

    public function update(Request $request, AdministrativeDocument $document): RedirectResponse
    {
        $data = $this->validateDocument($request);
        $storedPaths = [];
        try {
            DB::transaction(function () use ($request, $document, $data, &$storedPaths): void {
                $document->update($this->documentData($data) + ['updated_by' => $request->user()->id]);
                $this->storeAttachments($request, $document, $storedPaths);
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('success', 'Đã cập nhật hồ sơ văn bản.');
    }

    public function destroy(AdministrativeDocument $document): RedirectResponse
    {
        $paths = $document->attachments()->pluck('storage_path');
        DB::transaction(fn () => $document->delete());
        Storage::disk('local')->delete($paths->all());

        return redirect()->route('administration.documents.index')->with('success', 'Đã xóa hồ sơ văn bản và các file đính kèm.');
    }

    public function download(AdministrativeDocument $document, AdministrativeDocumentAttachment $attachment)
    {
        abort_unless($attachment->document_id === $document->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404, 'File không còn trên máy chủ.');

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroyAttachment(AdministrativeDocument $document, AdministrativeDocumentAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->document_id === $document->id, 404);
        $path = $attachment->storage_path;
        $attachment->delete();
        Storage::disk('local')->delete($path);

        return back()->with('success', 'Đã xóa file đính kèm.');
    }

    private function validateDocument(Request $request): array
    {
        return $request->validate([
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_symbol' => ['required', 'string', 'max:150'],
            'drafter' => ['required', 'string', 'max:150'],
            'document_date' => ['required', 'date'],
            'signer' => ['nullable', 'string', 'max:150'],
            'summary' => ['required', 'string', 'max:5000'],
            'destination' => ['nullable', 'string', 'max:3000'],
            'receiver' => ['nullable', 'string', 'max:200'],
            'storage_link' => ['nullable', 'url:http,https', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'scan_files' => ['nullable', 'array', 'max:5'],
            'scan_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
            'word_files' => ['nullable', 'array', 'max:5'],
            'word_files.*' => ['file', 'mimes:doc,docx,odt', 'max:20480'],
        ], [
            'storage_link.url' => 'Link lưu trữ phải bắt đầu bằng http:// hoặc https://.',
            'scan_files.*.mimes' => 'File scan chỉ nhận PDF, JPG hoặc PNG.',
            'word_files.*.mimes' => 'File văn bản chỉ nhận DOC, DOCX hoặc ODT.',
            'scan_files.*.max' => 'Mỗi file scan không được vượt quá 20 MB.',
            'word_files.*.max' => 'Mỗi file văn bản không được vượt quá 20 MB.',
        ]);
    }

    private function documentData(array $data): array
    {
        return collect($data)->only([
            'document_number', 'document_symbol', 'drafter', 'document_date', 'signer',
            'summary', 'destination', 'receiver', 'storage_link', 'notes',
        ])->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
    }

    private function storeAttachments(Request $request, AdministrativeDocument $document, array &$storedPaths): void
    {
        foreach (['scan_files' => 'scan', 'word_files' => 'word'] as $field => $kind) {
            foreach ($request->file($field, []) as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $name = Str::uuid().($extension ? '.'.$extension : '');
                $path = $file->storeAs('administrative-documents/'.$document->id.'/'.$kind, $name, 'local');
                $storedPaths[] = $path;
                $document->attachments()->create([
                    'kind' => $kind,
                    'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                    'storage_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        }
    }
}
