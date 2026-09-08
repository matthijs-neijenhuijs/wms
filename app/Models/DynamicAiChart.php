<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWarehouse;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicAiChart extends Model
{
    use BelongsToWarehouse;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'user_id',
        'title',
        'question',
        'chart_type',
        'selected_model',
        'metric_key',
        'query_sql',
        'query_bindings',
        'chart_payload',
        'score',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'query_bindings' => 'array',
            'chart_payload' => 'array',
            'meta' => 'array',
            'score' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DynamicAiChart $chart): void {
            if ($chart->warehouse_id) {
                return;
            }

            $tenant = Filament::getTenant();

            if ($tenant) {
                $chart->warehouse_id = $tenant->getKey();
            }
        });
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
