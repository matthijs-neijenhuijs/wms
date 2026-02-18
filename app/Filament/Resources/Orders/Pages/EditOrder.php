<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record->orderStatus?->canDeleteOrder() ?? true),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $isLocked = ! $this->record->orderStatus?->canEditOrder();
        $baseSchema = parent::form($schema);

        if ($isLocked) {
            $baseSchema->disabled();
        }

        return $baseSchema;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
