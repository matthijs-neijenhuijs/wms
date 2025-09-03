<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Livewire\Attributes\On;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    #[On('refresh-relation-manager')]

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                TextInput::make('firstname')
                    ->required()
                    ->maxLength(255),
                TextInput::make('lastname')
                    ->required()
                    ->maxLength(255),
                TextInput::make('street')
                    ->required()
                    ->maxLength(255),




            ]);
    }

    public function table(Table $table): Table
    {


        
        return $table

        
            ->recordTitleAttribute('firstname')
            ->columns([
                IconColumn::make('isBillingAddress')->label('Bill address')
                    ->boolean(),
                IconColumn::make('isDeliveryAddress')->label('Delivery address')
                    ->boolean(),
                TextColumn::make('firstname')
                    ->searchable(),

                TextColumn::make('lastname')
                    ->searchable(),
                TextColumn::make('street')
                    ->searchable(),


            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
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
