<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environmental_plans', function (Blueprint $table) {
            $table->string('laboratory_code')->nullable()->after('laboratory_name');
            $table->text('analysis_description')->nullable()->after('laboratory_code');
            $table->string('rla_no')->nullable()->after('analysis_description');
        });
    }

    public function down(): void
    {
        Schema::table('environmental_plans', function (Blueprint $table) {
            $table->dropColumn(['laboratory_code', 'analysis_description', 'rla_no']);
        });
    }
};
