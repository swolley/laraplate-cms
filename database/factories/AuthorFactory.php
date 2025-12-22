<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Helpers\HasDynamicContentFactory;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Models\Author;
use Modules\Core\Helpers\HasUniqueFactoryValues;
use Override;

final class AuthorFactory extends Factory
{
    use HasDynamicContentFactory, HasUniqueFactoryValues;

    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Author::class;

    protected EntityType $entityType = EntityType::AUTHORS;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $definition = $this->dynamicContentDefinition();
        $user = fake()->boolean() ? user_class()::inRandomOrder()->first() : null;

        $baseName = $user ? $user->name : (fake()->boolean() ? fake()->name() : fake()->userName());
        $name = $baseName . '-' . fake()->unique()->numerify('########');

        return $definition + [
            'name' => $name,
            'user_id' => $user?->id,
        ];
    }

    #[Override]
    public function configure(): self
    {
        /** @param Model&HasDynamicContents $model */
        return $this->afterMaking(function (Author $model): void {
            $this->fillDynamicContents($model, [
                'public_email' => fake()->boolean() ? fake()->unique()->email() : ($model->user ? $model->user->email : null),
            ]);
        });
    }
}
