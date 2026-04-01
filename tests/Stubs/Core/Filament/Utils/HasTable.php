<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Utils;

use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Minimal Core trait stub so {@see \Modules\Cms\Filament\Utils\HasTable} can compose without the full Core stack.
 */
trait HasTable
{
    protected static function configureTable(
        Table $table,
        ?callable $columns = null,
        ?callable $actions = null,
        array $fixedActions = [],
        ?callable $filters = null,
    ): Table {
        return $table;
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
    ): void {}

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
    ): void {}
}
