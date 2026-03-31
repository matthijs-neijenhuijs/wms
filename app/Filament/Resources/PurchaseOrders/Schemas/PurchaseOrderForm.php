<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Models\Order;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                DatePicker::make('expected_delivery_date')
                    ->required(),
                Toggle::make('processed')
                    ->inline(false),
                Toggle::make('completed')
                    ->inline(false),
                Textarea::make('comments')
                    ->columnSpanFull(),
            ]);
    }
}
