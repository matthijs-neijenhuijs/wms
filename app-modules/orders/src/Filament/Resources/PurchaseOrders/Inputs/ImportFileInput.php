<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Inputs;

use Filament\Forms\Components\FileUpload;

class ImportFileInput
{
    public static function make(): FileUpload
    {
        return FileUpload::make('import_file')
            ->label(__('CSV Or Excel File'))
            ->helperText(__('Upload a CSV or XLSX file with barcode and quantity columns.'))
            ->acceptedFileTypes([
                'text/csv',
                'text/plain',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->storeFiles(false)
            ->visibility('private')
            ->required()
            ->columnSpanFull();
    }
}
