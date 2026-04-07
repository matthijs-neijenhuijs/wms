<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase order details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('expected_delivery_date')
                            ->required(),
                        FileUpload::make('import_file')
                            ->label('CSV or Excel file')
                            ->helperText('Upload a CSV or XLSX file with `barcode` and `quantity` columns.')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->storeFiles(false)
                            ->visibility('private')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('comments')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
