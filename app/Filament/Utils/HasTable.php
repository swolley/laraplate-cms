<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Utils;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Preset;
use Modules\Core\Enums\CoreTables;
use Modules\Core\Filament\Utils\HasTable as CoreHasTable;
use Modules\Core\Models\Concerns\HasDynamicContents;
use ReflectionClass;

trait HasTable
{
    use CoreHasTable {
        configureColumns as coreConfigureColumns;
        configureFilters as coreConfigureFilters;
        configureTable as coreConfigureTable;
    }

    /**
     * @param  list<Action|ActionGroup|BulkAction>  $fixedActions
     */
    protected static function configureTable(Table $table, ?callable $columns = null, ?callable $actions = null, array $fixedActions = [], ?callable $filters = null): Table
    {
        $model = $table->getModel();

        if ($model !== null) {
            $model_instance = new ReflectionClass($model)->newInstanceWithoutConstructor();

            if (self::hasDynamicContents($model_instance)) {
                $table->groups([
                    Group::make('presettable.entity.name')
                        ->label('Entity'),
                    Group::make('presettable.preset.name')
                        ->label('Preset'),
                ]);
            }
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
        ?User $user,
    ): void {
        $class = $model_instance::class;
        $entity_type = method_exists($class, 'getEntityType') ? $class::getEntityType() : null;

        if ($entity_type instanceof EntityType) {
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

    private static function resolveEntityType(Model $model_instance): ?EntityType
    {
        if (! self::hasDynamicContents($model_instance)) {
            return null;
        }

        $class = $model_instance::class;

        $entity_type = $class::getEntityType();

        return $entity_type instanceof EntityType ? $entity_type : null;
    }

    /**
     * Preset id => "Entity name - Preset name" for the Filament preset multi-select.
     *
     * @return array<int, string>
     */
    private static function presetSelectFilterOptions(EntityType $entity_type): array
    {
        $presets_table = CoreTables::Presets->value;
        $entities_table = CoreTables::Entities->value;
        $options = [];

        Preset::query()
            ->forActiveEntityOfType($entity_type)
            ->join($entities_table, "{$presets_table}.entity_id", '=', "{$entities_table}.id")
            ->orderBy("{$entities_table}.name")
            ->orderBy("{$presets_table}.name")
            ->select(
                "{$presets_table}.id",
                "{$presets_table}.name",
                "{$entities_table}.name as entity_name",
            )
            ->each(function (Preset $preset) use (&$options): void {
                self::appendPresetSelectFilterOption($options, $preset);
            });

        return $options;
    }

    /**
     * @param  array<int, string>  $options
     */
    private static function appendPresetSelectFilterOption(array &$options, Preset $preset): void
    {
        $id = $preset->getAttribute('id');
        $preset_name = $preset->getAttribute('name');
        $entity_name = $preset->getAttribute('entity_name');

        if (! is_numeric($id)) {
            return;
        }

        $options[(int) $id] = (is_string($entity_name) ? $entity_name : '')
            . ' - '
            . (is_string($preset_name) ? $preset_name : '');
    }
}
