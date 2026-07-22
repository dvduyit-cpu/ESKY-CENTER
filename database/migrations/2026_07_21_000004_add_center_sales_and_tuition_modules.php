<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_collaborators', function (Blueprint $table) {
            $table->id(); $table->string('code', 30)->unique(); $table->string('name');
            $table->string('phone', 30)->nullable(); $table->string('email')->nullable(); $table->string('address')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0); $table->boolean('active')->default(true);
            $table->text('note')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('language_courses', function (Blueprint $table) {
            $table->id(); $table->string('code', 30)->unique(); $table->string('name'); $table->string('textbook')->nullable();
            $table->decimal('tuition', 14, 2)->default(0); $table->decimal('duration_hours', 8, 2)->default(0);
            $table->unsignedSmallInteger('sessions')->default(0); $table->boolean('active')->default(true);
            $table->text('description')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('language_discount_policies', function (Blueprint $table) {
            $table->id(); $table->string('code', 30)->unique(); $table->string('name');
            $table->decimal('percentage', 5, 2); $table->string('eligible_subject');
            $table->date('starts_at')->nullable(); $table->date('ends_at')->nullable();
            $table->boolean('active')->default(true); $table->text('note')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::table('language_leads', function (Blueprint $table) {
            $table->foreignId('language_collaborator_id')->nullable()->after('consultant_user_id')->constrained()->nullOnDelete();
            $table->foreignId('language_course_id')->nullable()->after('language_program_id')->constrained()->nullOnDelete();
            $table->foreignId('converted_student_id')->nullable()->after('language_course_id')->constrained('language_students')->nullOnDelete();
        });
        Schema::create('language_tuition_charges', function (Blueprint $table) {
            $table->id(); $table->string('code', 30)->unique();
            $table->foreignId('language_student_id')->constrained()->restrictOnDelete();
            $table->foreignId('language_lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('language_course_id')->constrained()->restrictOnDelete();
            $table->foreignId('language_class_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('language_discount_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('original_amount', 14, 2); $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0); $table->decimal('payable_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0); $table->date('due_date')->nullable();
            $table->string('status', 30)->default('unpaid'); $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('language_tuition_payments', function (Blueprint $table) {
            $table->id(); $table->string('receipt_code', 30)->unique();
            $table->foreignId('language_tuition_charge_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2); $table->dateTime('paid_at');
            $table->string('payment_method', 30)->default('cash'); $table->string('reference')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('note')->nullable(); $table->timestamps();
        });
        Schema::create('language_monthly_target_records', function (Blueprint $table) {
            $table->id(); $table->unsignedSmallInteger('record_year'); $table->unsignedTinyInteger('record_month');
            $table->foreignId('language_student_id')->constrained('language_students','id','lmtr_student_fk')->restrictOnDelete();
            $table->foreignId('language_lead_id')->nullable()->constrained('language_leads','id','lmtr_lead_fk')->nullOnDelete();
            $table->foreignId('language_collaborator_id')->nullable()->constrained('language_collaborators','id','lmtr_collab_fk')->nullOnDelete();
            $table->foreignId('language_course_id')->constrained('language_courses','id','lmtr_course_fk')->restrictOnDelete();
            $table->foreignId('language_tuition_payment_id')->unique('lmtr_payment_uq')->constrained('language_tuition_payments','id','lmtr_payment_fk')->restrictOnDelete();
            $table->decimal('quantity', 10, 2)->default(1); $table->decimal('revenue', 14, 2);
            $table->text('note')->nullable(); $table->timestamps();
            $table->index(['record_year','record_month']);
        });

        $now = now();
        foreach ([
            ['language_collaborators','Cộng tác viên trung tâm','bi-person-vcard',24],
            ['language_courses','Khóa học trung tâm','bi-book',25],
            ['language_discounts','Chế độ miễn giảm','bi-percent',26],
            ['language_tuition','Thu học phí','bi-cash-coin',27],
            ['language_targets','Chỉ tiêu trung tâm theo tháng','bi-clipboard-data',28],
        ] as [$code,$name,$icon,$sort]) {
            DB::table('modules')->updateOrInsert(['code'=>$code], ['name'=>$name,'icon'=>$icon,'sort_order'=>$sort,'created_at'=>$now,'updated_at'=>$now]);
            $moduleId = DB::table('modules')->where('code',$code)->value('id');
            foreach (DB::table('roles')->get(['id','code']) as $role) {
                $admin = $role->code === 'admin';
                DB::table('role_permissions')->updateOrInsert(['role_id'=>$role->id,'module_id'=>$moduleId], ['can_view'=>$admin,'can_create'=>$admin,'can_update'=>$admin,'can_delete'=>$admin,'can_export'=>$admin,'created_at'=>$now,'updated_at'=>$now]);
            }
        }
    }

    public function down(): void
    {
        $ids=DB::table('modules')->whereIn('code',['language_collaborators','language_courses','language_discounts','language_tuition','language_targets'])->pluck('id');
        DB::table('role_permissions')->whereIn('module_id',$ids)->delete(); DB::table('modules')->whereIn('id',$ids)->delete();
        Schema::dropIfExists('language_monthly_target_records'); Schema::dropIfExists('language_tuition_payments'); Schema::dropIfExists('language_tuition_charges');
        Schema::table('language_leads', function(Blueprint $table){$table->dropConstrainedForeignId('converted_student_id');$table->dropConstrainedForeignId('language_course_id');$table->dropConstrainedForeignId('language_collaborator_id');});
        Schema::dropIfExists('language_discount_policies'); Schema::dropIfExists('language_courses'); Schema::dropIfExists('language_collaborators');
    }
};
