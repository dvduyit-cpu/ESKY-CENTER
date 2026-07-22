<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE kpi_targets DROP FOREIGN KEY fk_kpi_targets_course');
        DB::statement('ALTER TABLE kpi_targets MODIFY course_id BIGINT UNSIGNED NULL');
        DB::statement('UPDATE kpi_targets SET course_id = NULL');
        DB::statement('ALTER TABLE kpi_targets ADD CONSTRAINT fk_kpi_targets_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('DELETE FROM kpi_targets WHERE course_id IS NULL');
        DB::statement('ALTER TABLE kpi_targets DROP FOREIGN KEY fk_kpi_targets_course');
        DB::statement('ALTER TABLE kpi_targets MODIFY course_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE kpi_targets ADD CONSTRAINT fk_kpi_targets_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT');
    }
};
