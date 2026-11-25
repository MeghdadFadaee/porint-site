<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_daily_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_on')->index();
            $table->unsignedBigInteger('views');
            $table->unique(['design_id', 'viewed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_daily_views');
    }
};
