<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->date('due_date');
            $table->string('status', 20)->default('draft');
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->json('review_payload')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'week_start']);
            $table->index(['week_start', 'status']);
        });

        Schema::create('administrative_weekly_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('administrative_weekly_reports')->cascadeOnDelete();
            $table->string('type', 30);
            $table->text('content');
            $table->text('normalized_content')->nullable();
            $table->unsignedTinyInteger('quality_score')->default(0);
            $table->json('review_payload')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['report_id', 'type']);
        });

        Schema::create('administrative_weekly_compilations', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')->unique();
            $table->date('week_end');
            $table->longText('content');
            $table->json('source_item_ids')->nullable();
            $table->json('duplicate_groups')->nullable();
            $table->foreignId('compiled_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('compiled_at');
            $table->timestamps();
        });

        Schema::create('administrative_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 100)->nullable();
            $table->string('document_symbol', 150);
            $table->string('drafter', 150);
            $table->date('document_date');
            $table->string('signer', 150)->nullable();
            $table->text('summary');
            $table->text('destination')->nullable();
            $table->string('receiver', 200)->nullable();
            $table->text('storage_link')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['document_date', 'document_symbol']);
        });

        Schema::create('administrative_document_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('administrative_documents')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['document_id', 'kind']);
        });

        $now = now();
        DB::table('modules')->updateOrInsert(
            ['code' => 'administration'],
            [
                'name' => 'Hành chính - báo cáo & văn thư',
                'icon' => 'bi-building-check',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $moduleId = DB::table('modules')->where('code', 'administration')->value('id');
        foreach (DB::table('roles')->get(['id', 'code']) as $role) {
            $management = in_array($role->code, ['admin', 'leader', 'director', 'deputy_director'], true);
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $role->id, 'module_id' => $moduleId],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => $management,
                    'can_export' => $management,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('code', 'administration')->value('id');
        if ($moduleId) {
            DB::table('user_permissions')->where('module_id', $moduleId)->delete();
            DB::table('role_permissions')->where('module_id', $moduleId)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }
        Schema::dropIfExists('administrative_document_attachments');
        Schema::dropIfExists('administrative_documents');
        Schema::dropIfExists('administrative_weekly_compilations');
        Schema::dropIfExists('administrative_weekly_report_items');
        Schema::dropIfExists('administrative_weekly_reports');
    }
};
