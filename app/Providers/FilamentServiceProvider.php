<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\DateFormat;
use App\Enums\TimeFormat;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /* Global */
        FilamentTimezone::set(config('app.timezone_display'));

        /* Modals */
        ModalComponent::closedByClickingAway(false);

        /* Schemas */
        Schema::configureUsing(function (Schema $schema): void {
            $schema
                ->defaultDateDisplayFormat(DateFormat::DEFAULT)
                ->defaultTimeDisplayFormat(TimeFormat::DEFAULT)
                ->defaultDateTimeDisplayFormat(DateFormat::DEFAULT_WITH_TIME)
                ->dense();
        });

        /* Tables */
        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultDateDisplayFormat(DateFormat::DEFAULT)
                ->defaultTimeDisplayFormat(TimeFormat::DEFAULT)
                ->defaultDateTimeDisplayFormat(DateFormat::DEFAULT_WITH_TIME);

            $table
                ->deferLoading();

            $table
                ->extremePaginationLinks()
                ->defaultPaginationPageOption(25)
                ->paginationPageOptions([25, 50, 100]);

            $table
                ->emptyStateIcon(Heroicon::OutlinedSparkles)
                ->emptyStateHeading(__('No records yet.'));
        });

        Column::configureUsing(function (Column $column): void {
            $column
                ->placeholder('-');
        });

        ImageColumn::configureUsing(function (ImageColumn $column): void {
            $column
                ->imageSize('2rem');
        });

        SelectFilter::configureUsing(function (SelectFilter $filter): void {
            $filter
                ->searchable()
                ->preload()
                ->native(false)
                ->optionsLimit(20);
        });

        TextInput::configureUsing(function (TextInput $input): void {
            $input
                ->maxLength(255)
                ->trim(); /* https://filamentphp.com/docs/4.x/forms/text-input#trimming-whitespace */
        });

        Select::configureUsing(function (Select $input): void {
            $input
                ->searchable()
                ->preload()
                ->native(false)
                ->preload()
                ->optionsLimit(20);
        });

        MorphToSelect::configureUsing(function (MorphToSelect $input): void {
            $input
                ->searchable()
                ->native(false)
                ->optionsLimit(20);
        });

        Toggle::configureUsing(function (Toggle $input): void {
            $input
                ->offColor('gray')
                ->onColor('primary');
        });

        DatePicker::configureUsing(function (DatePicker $input): void {
            $input
                ->native(false)
                ->closeOnDateSelection()
                ->displayFormat(DateFormat::DEFAULT);
        });

        TimePicker::configureUsing(function (TimePicker $input): void {
            $input
                ->native(false)
                ->seconds(false)
                ->closeOnDateSelection()
                ->displayFormat(TimeFormat::DEFAULT);
        });

        DateTimePicker::configureUsing(function (DateTimePicker $input): void {
            $input
                ->native(false)
                ->seconds(false)
                ->closeOnDateSelection()
                ->displayFormat(DateFormat::DEFAULT_WITH_TIME);
        });

        TagsInput::configureUsing(function (TagsInput $input): void {
            $input
                ->color('primary');
        });

        /* Infolists */
        TextEntry::configureUsing(function (TextEntry $entry): void {
            $entry
                ->placeholder('-');
        });

        /* Actions */
        Page::alignFormActionsEnd();

        Action::configureUsing(function (Action $action): void {
            $action
                ->modalFooterActionsAlignment(Alignment::End);
        });

        CreateAction::configureUsing(function (CreateAction $action): void {
            $action
                ->createAnother(false);
        });

        AttachAction::configureUsing(function (AttachAction $action): void {
            $action
                ->multiple()
                ->preloadRecordSelect()
                ->attachAnother(false);
        });

        DetachAction::configureUsing(function (DetachAction $action): void {
            $action
                ->requiresConfirmation();
        });

        ImportAction::configureUsing(function (ImportAction $action): void {
            $action
                ->maxRows(100_000);
        });

    }
}
