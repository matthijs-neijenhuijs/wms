<?php

namespace App\Filament\Resources\ApiKeys\Pages;

use App\Filament\Resources\ApiKeys\ApiKeyResource;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected ?string $plainTextKey = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            abort(403);
        }

        $token = Str::random(64);
        $this->plainTextKey = $token;

        $data['warehouse_id'] = $tenant->id;
        $data['key_hash'] = hash('sha256', $token);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->plainTextKey) {
            return;
        }

        Notification::make()
            ->title('API key created')
            ->body("Copy this key now: {$this->plainTextKey}")
            ->success()
            ->persistent()
            ->send();
    }
}
