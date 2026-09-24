<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Pages;

use AlizHarb\ActivityLog\Resources\ActivityLogs\Tables\ActivityLogTable;
use App\Filament\Resources\ActivityLogs\Columns\StockMutationColumns;
use BackedEnum;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Products\Filament\Resources\Products\ProductResource;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Spatie\Activitylog\Models\Activity;

/**
 * Combined "History" tab for a Product: its own field changes
 * (`subject_type = Product`) and its StockProduct row's quantity changes
 * (`subject_type = StockProduct`) in one table, newest first. No single
 * Eloquent relationship spans both subject types, so unlike every other
 * module's History tab this is not a `ManageRelatedRecords` page - it
 * implements `Tables\Contracts\HasTable` directly with a manually-combined
 * `Activity` query.
 */
class ManageProductHistory extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = ProductResource::class;

    protected static ?string $navigationLabel = 'History';

    protected static ?string $breadcrumb = 'History';

    protected static ?string $title = 'History';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::canAccess(['record' => $this->getRecord()]), 403);
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Product $record */
        $record = $this->getRecord();

        $table = ActivityLogTable::configure($table);
        $existingColumns = array_values($table->getColumns());

        return $table
            ->query(function () use ($record): Builder {
                $stockProductId = $record->stockProduct?->getKey();

                return Activity::query()
                    ->with(['causer', 'subject'])
                    ->where(function (Builder $query) use ($record): void {
                        $query->where('subject_type', $record->getMorphClass())
                            ->where('subject_id', $record->getKey());
                    })
                    ->when($stockProductId, function (Builder $query, int $stockProductId): void {
                        $query->orWhere(function (Builder $query) use ($stockProductId): void {
                            $query->where('subject_type', (new StockProduct)->getMorphClass())
                                ->where('subject_id', $stockProductId);
                        });
                    });
            })
            ->columns([
                StockMutationColumns::direction(),
                StockMutationColumns::quantityDelta(),
                StockMutationColumns::orderReference(),
                ...$existingColumns,
            ])
            ->pushFilters([
                StockMutationColumns::directionFilter(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
