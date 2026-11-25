<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garment_templates', function (Blueprint $table): void {
            $table->unique(['garment', 'color', 'side']);
        });
    }

    public function down(): void
    {
        Schema::table('garment_templates', function (Blueprint $table): void {
            $table->dropUnique(['garment', 'color', 'side']);
        });
    }
};
