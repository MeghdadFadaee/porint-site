<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Design;
use App\Support\Artwork;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rose = Collection::firstOrCreate(['slug_en' => 'rose-and-thorn'], [
            'slug_fa' => 'رز-و-خار',
            'name_fa' => 'رز و خار',
            'name_en' => 'Rose & Thorn',
            'description_fa' => 'رزهای سرخ، خارهای سیاه و نقش‌های گوتیک برای لباس‌های روزمره.',
            'description_en' => 'Crimson roses, black thorns, and gothic motifs for everyday clothes.',
            'cover' => 'porint/rose-sheet.webp',
            'published_at' => now(),
        ]);

        Collection::firstOrCreate(['slug_en' => 'sound-wave'], [
            'slug_fa' => 'موج-صدا',
            'name_fa' => 'موج صدا',
            'name_en' => 'Sound Wave',
            'description_fa' => 'از صفحهٔ وینیل تا هدفون، گیتار و ضربان موسیقی.',
            'description_en' => 'Vinyl, headphones, guitars, and the pulse of music.',
            'cover' => 'porint/sound-sheet.webp',
            'published_at' => now(),
        ]);

        if (! Design::where('slug_en', 'skull-and-roses')->exists()) {
            $source = 'originals/demo-skull-roses.png';
            Storage::disk('local')->put($source, file_get_contents(database_path('seeders/artwork/skull-roses.png')));
            Design::create([
                'collection_id' => $rose->id,
                'slug_fa' => 'جمجمه-و-رز',
                'slug_en' => 'skull-and-roses',
                'name_fa' => 'جمجمه و رز',
                'name_en' => 'Skull & Roses',
                'description_fa' => 'جمجمهٔ نقره‌ای، رزهای سرخ و شاخه‌های خاردار. طرح را روی تی‌شرت یا هودی در رنگ‌های مختلف ببینید.',
                'description_en' => 'An engraved silver skull among crimson roses and thorns. See the motif on a tee, hoodie, or crewneck.',
                'original_path' => $source,
                'artwork_path' => app(Artwork::class)->prepare($source),
                'width_cm' => 18,
                'height_cm' => 24,
                'illustrative' => true,
                'published_at' => now(),
            ]);
        }

        $this->call(GarmentTemplateSeeder::class);
    }
}
