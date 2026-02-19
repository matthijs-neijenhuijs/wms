<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    #[On('refresh-relation-manager')]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact')
                    ->schema([
                        TextInput::make('company')
                            ->maxLength(255),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->required(),
                        TextInput::make('initials')
                            ->maxLength(20),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('telephone_number')
                            ->label('Telephone number')
                            ->maxLength(50),
                    ])
                    ->columns(2),
                Section::make('Address')
                    ->schema([
                        TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('zipcode')
                            ->required()
                            ->maxLength(20),
                        TextInput::make('city')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('region')
                            ->maxLength(255),
                        TextInput::make('country')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

            ]);
    }

    public function table(Table $table): Table
    {

        return $table

            ->recordTitleAttribute('name')
            ->columns([
                IconColumn::make('isBillingAddress')->label('Bill address')
                    ->boolean(),
                IconColumn::make('isDeliveryAddress')->label('Delivery address')
                    ->boolean(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
