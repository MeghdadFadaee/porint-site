<?php

namespace Database\Seeders;

use App\Models\GarmentTemplate;
use Illuminate\Database\Seeder;

class GarmentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $colors = ['ink', 'paper', 'navy', 'olive'];
        $frontAnchors = [
            'tee' => ['chest' => 42, 'upper-front' => 34, 'mid-front' => 53, 'lower-front' => 66],
            'hoodie' => ['chest' => 42, 'upper-front' => 40, 'mid-front' => 44],
            'crewneck' => ['chest' => 43, 'upper-front' => 34, 'mid-front' => 54, 'lower-front' => 65],
            'oversized-tee' => ['chest' => 43, 'upper-front' => 34, 'mid-front' => 53, 'lower-front' => 65],
            'long-sleeve' => ['chest' => 42, 'upper-front' => 33, 'mid-front' => 53, 'lower-front' => 65],
            'polo' => ['chest' => 50, 'mid-front' => 58, 'lower-front' => 68],
        ];

        foreach ($frontAnchors as $garment => $anchors) {
            $placements = [];
            foreach ($anchors as $key => $y) {
                $width = $key === 'chest' ? 34 : 30;
                $height = $garment === 'hoodie' && $key !== 'chest' ? 24 : 28;
                $placements[$key] = $this->placement(50, $y, $width, $height);
            }

            $left = $garment === 'polo' ? 61 : 58;
            $placements['left-chest'] = $this->placement($left, 35, 12, 15);
            $placements['right-chest'] = $this->placement(100 - $left, 35, 12, 15);

            foreach ($garment === 'crewneck' ? [...$colors, 'crimson'] : $colors as $color) {
                $this->saveTemplate($garment, $color, 'front', $placements);
            }
        }

        foreach ($colors as $color) {
            $this->saveTemplate('tee', $color, 'back', [
                'back' => $this->placement(50, 45, 32, 30),
                'upper-back' => $this->placement(50, 34, 30, 25),
                'lower-back' => $this->placement(50, 63, 30, 28),
            ]);
        }
    }

    private function placement(int $x, int $y, int $width, int $height): array
    {
        return ['x' => $x, 'y' => $y, 'max_width_cm' => $width, 'max_height_cm' => $height];
    }

    private function saveTemplate(string $garment, string $color, string $side, array $placements): void
    {
        $file = $side === 'front' ? "$garment-$color" : "$garment-$color-back";
        $photo = "porint/garments/$file.webp";
        [$width] = getimagesize(public_path($photo));

        $nameFa = __("studio.garment_labels.$garment", [], 'fa').' '.__("studio.color_labels.$color", [], 'fa');
        $nameEn = __("studio.color_labels.$color", [], 'en').' '.__("studio.garment_labels.$garment", [], 'en');

        GarmentTemplate::firstOrCreate(['garment' => $garment, 'color' => $color, 'side' => $side], [
            'name_fa' => $side === 'back' ? "$nameFa (پشت)" : $nameFa,
            'name_en' => $side === 'back' ? "$nameEn (back)" : $nameEn,
            'photo' => $photo,
            'photo_width_px' => $width,
            'pixels_per_cm' => round(5.9 * $width / 627, 3),
            'placements' => $placements,
            'illustrative' => true,
        ]);
    }
}
