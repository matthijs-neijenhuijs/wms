<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Pages;

use App\Services\PurchaseOrderImportService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Orders\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Modules\Orders\Models\PurchaseOrder;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected TemporaryUploadedFile|UploadedFile|string|null $importFile = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->importFile = $data['import_file'] ?? null;

        unset($data['import_file']);

        $data['warehouse_id'] = Filament::getTenant()?->getKey();
        $data['processed'] = false;
        $data['completed'] = false;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = PurchaseOrder::query()->create($data);

            app(PurchaseOrderImportService::class)->import($purchaseOrder, $this->importFile);

            return $purchaseOrder;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
