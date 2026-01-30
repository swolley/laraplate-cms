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
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Preset;
use Modules\Core\Filament\Utils\HasTable as CoreHasTable;
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
                Group::make('entity.name')
                    ->label('Entity'),
                Group::make('preset.name')
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
                TextColumn::make('entity.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('preset.name')
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
            $entity_type = EntityType::tryFrom($model_instance->getTable());

            if ($entity_type) {
                $table->pushFilters([
                    SelectFilter::make('preset')
                        ->label('Preset')
                        ->multiple()
                        ->options(fn () => Preset::query()
                            ->join('entities', 'presets.entity_id', '=', 'entities.id')
                            ->where('presets.' . Preset::activationColumn(), true)
                            ->whereHas('entity', fn (Builder $query) => $query->where([
                                'entities.' . Entity::activationColumn() => true,
                                'entities.type' => $entity_type,
                            ]))
                            ->orderBy('entities.name')
                            ->orderBy('presets.name')
                            ->get(['presets.id', 'presets.name', 'presets.entity_id', 'entities.name'])
                            ->mapWithKeys(static fn (Preset $preset): array => [$preset->id => $preset->entity->name . ' - ' . $preset->name]))
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
}
