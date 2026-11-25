<?php

namespace App\Support;

use App\Models\Design;
use App\Models\GarmentTemplate;

class GarmentPreview
{
    public function options(Design $design): array
    {
        return GarmentTemplate::orderBy('id')->get()->map(function (GarmentTemplate $template) use ($design): array {
            $placements = [];
            foreach ($template->placements as $key => $placement) {
                if ($design->width_cm <= $placement['max_width_cm'] && $design->height_cm <= $placement['max_height_cm']) {
                    $placements[$key] = ['x' => $placement['x'], 'y' => $placement['y']];
                }
            }

            return [
                'name' => $template->{'name_'.app()->getLocale()},
                'garment' => $template->garment,
                'color' => $template->color,
                'side' => $template->side,
                'photo' => $template->photoUrl(),
                'width' => 100 * $design->width_cm * $template->pixels_per_cm / $template->photo_width_px,
                'placements' => $placements,
                'illustrative' => $template->illustrative,
            ];
        })->filter(fn (array $template) => $template['placements'] !== [])->values()->all();
    }
}
