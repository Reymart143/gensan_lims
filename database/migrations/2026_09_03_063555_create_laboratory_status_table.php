<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laboratory_status', function (Blueprint $table) {
              $table->id();

            $table->string('laboratory_code');
            $table->string('rla_no');

            // Default status is 0
            $table->integer('status')->default(0);

            $table->timestamps();

            // Prevent duplicate laboratory_code + rla_no
            $table->unique(
                ['laboratory_code', 'rla_no'],
                'laboratory_status_labcode_rla_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_status');
    }
};
