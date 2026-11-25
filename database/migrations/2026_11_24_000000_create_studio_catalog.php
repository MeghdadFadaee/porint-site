<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_staff')->default(false);
        });

        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $this->localizedCopy($table);
            $table->string('cover')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('designs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained()->restrictOnDelete();
            $this->localizedCopy($table);
            $table->decimal('width_cm', 6, 2);
            $table->decimal('height_cm', 6, 2);
            $table->string('original_path');
            $table->string('artwork_path')->nullable();
            $table->boolean('illustrative')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('garment_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name_fa');
            $table->string('name_en');
            $table->string('garment');
            $table->string('color');
            $table->string('side')->default('front');
            $table->string('photo')->unique();
            $table->unsignedInteger('photo_width_px');
            $table->decimal('pixels_per_cm', 8, 3);
            $table->json('placements');
            $table->boolean('illustrative')->default(false);
            $table->timestamps();
        });
    }

    private function localizedCopy(Blueprint $table): void
    {
        foreach (['fa', 'en'] as $locale) {
            $table->string("name_$locale");
            $table->string("slug_$locale")->unique();
            $table->text("description_$locale");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('garment_templates');
        Schema::dropIfExists('designs');
        Schema::dropIfExists('collections');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_staff');
        });
    }
};
