<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DesignDailyView extends Model
{
    public const TIMEZONE = 'Asia/Tehran';

    public $timestamps = false;

    public static function today(): CarbonImmutable
    {
        return now(self::TIMEZONE)->toImmutable()->startOfDay();
    }

    public static function recordView(int $designId, string $date): void
    {
        static::query()->upsert([
            ['design_id' => $designId, 'viewed_on' => $date, 'views' => 1],
        ], ['design_id', 'viewed_on'], ['views' => DB::raw('design_daily_views.views + 1')]);
    }
}
