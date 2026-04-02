<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Utils;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Filament\Utils\HasTable as CoreHasTable;
use Modules\Core\Helpers\HasDynamicContents;
use Modules\Core\Models\Preset;
use ReflectionClass;

trait HasTable
{
    use CoreHasTable {
        configureColumns as coreConfigureColumns;
        configureFilters as coreConfigureFilters;
        configureTable as coreConfigureTable;
    }

    protected static function configureTable(Table $table, ?callable $columns = null, ?callable $actions = null, array $fixedActions = [], ?callable $filters = null): Table
    {
        $model = $table->getModel();
        $model_instance = new ReflectionClass($model)->newInstanceWithoutConstructor();

        if ($model && self::hasDynamicContents($model_instance)) {
            $table->groups([
                Group::make('presettable.entity.name')
                    ->label('Entity'),
                Group::make('presettable.preset.name')
                    ->label('Preset'),
            ]);
        }

        return self::coreConfigureTable(
            $table,
            $columns,
            $actions,
            $fixedActions,
            $filters,
        );
    }

    private static function configureColumns(
        Table $table,
        bool $hasSoftDeletes,
        bool $hasValidity,
        bool $hasLocks,
        bool $hasSorts,
        bool $hasActivation,
        bool $hasTranslations,
        ?callable $columns,
        Model $model_instance,
    ): void {
        if (self::hasDynamicContents($model_instance)) {
            $table->pushColumns([
                TextColumn::make('presettable.entity.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('presettable.preset.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
        }

        self::coreConfigureColumns(
            $table,
            $hasSoftDeletes,
            $hasValidity,
            $hasLocks,
            $hasSorts,
            $hasActivation,
            $hasTranslations,
            $columns,
            $model_instance,
        );
    }

    private static function configureFilters(
        Table $table,
        bool $hasSoftDeletes,
        bool $hasValidity,
        bool $hasLocks,
        bool $hasActivation,
        bool $hasTranslations,
        ?callable $filters,
        Model $model_instance,
        string $permissionsPrefix,
        User $user,
    ): void {
        if (self::hasDynamicContents($model_instance)) {
            $entity_type = $model_instance::getEntityType();
            $entity_type = $entity_type::tryFrom($model_instance->getTable());

            if ($entity_type) {
                $table->pushFilters([
                    SelectFilter::make('preset')
                        ->label('Preset')
                        ->multiple()
                        ->options(fn (): array => self::presetSelectFilterOptions($entity_type))
                        ->query(static fn (Builder $query, array $data): Builder => $query->when(
                            $data['values'],
                            static fn (Builder $query, $values): Builder => $query->whereIn('preset_id', $values),
                        )),
                ]);
            }
        }

        self::coreConfigureFilters(
            $table,
            $hasSoftDeletes,
            $hasValidity,
            $hasLocks,
            $hasActivation,
            $hasTranslations,
            $filters,
            $model_instance,
            $permissionsPrefix,
            $user,
        );
    }

    private static function hasDynamicContents(Model $model_instance): bool
    {
        return class_uses_trait($model_instance::class, HasDynamicContents::class);
    }

    /**
     * Preset id => "Entity name - Preset name" for the Filament preset multi-select.
     *
     * @return array<int, string>
     */
    private static function presetSelectFilterOptions(EntityType $entity_type): array
    {
        return Preset::query()
            ->forActiveEntityOfType($entity_type)
            ->join('entities', 'presets.entity_id', '=', 'entities.id')
            ->orderBy('entities.name')
            ->orderBy('presets.name')
            ->get(['presets.id', 'presets.name', 'presets.entity_id', 'entities.name'])
            ->mapWithKeys(static fn (Preset $preset): array => [$preset->id => $preset->entity->name . ' - ' . $preset->name])
            ->all();
    }
}
