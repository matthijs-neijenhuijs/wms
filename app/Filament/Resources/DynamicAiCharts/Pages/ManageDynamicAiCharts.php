<?php

namespace App\Filament\Resources\DynamicAiCharts\Pages;

use App\Filament\Resources\DynamicAiCharts\DynamicAiChartResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDynamicAiCharts extends ManageRecords
{
    protected static string $resource = DynamicAiChartResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
