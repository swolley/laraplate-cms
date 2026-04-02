<?php

declare(strict_types=1);

namespace Modules\Core\Overrides;

use Illuminate\Database\Eloquent\Factories\Factory as BaseFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Helpers\HasDynamicContentFactory;
use Override;

/**
 * Minimal definition for PHPStan and submodule tests when laraplate-core is not on the classpath.
 * The real implementation lives in the Core module repository.
 *
 * @template TModel of Model = Model
 *
 * @extends BaseFactory<TModel>
 */
abstract class Factory extends BaseFactory
{
    use HasDynamicContentFactory;

    abstract protected function definitionsArray(): array;

    #[Override]
    public function definition(): array
    {
        return $this->definitionsArray();
    }

    protected function beforeFactoryMaking(Model $model): void {}

    protected function afterFactoryMaking(Model $model): void {}

    protected function beforeFactoryCreating(Model $model): void {}

    protected function afterFactoryCreating(Model $model): void {}

    /**
     * @return array<string, mixed>
     */
    protected function translatedFieldsArray(Model $model): array
    {
        return [];
    }
}
