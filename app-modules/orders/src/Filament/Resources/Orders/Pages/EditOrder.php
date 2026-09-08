<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Models\Order;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $navigationLabel = 'General';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        /** @var Order $order */
        $order = $this->record;

        return [
            DeleteAction::make()
                ->visible(fn () => $order->orderStatus?->canDeleteOrder() ?? true),
        ];
    }

    public function form(Schema $schema): Schema
    {
        /** @var Order $order */
        $order = $this->record;
        $isLocked = ! $order->orderStatus?->canEditOrder();
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
