<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Model;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Models\Author;
use Override;

final class AuthorFactory extends DynamicContentFactory
{
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
        $definition = parent::definition();
        $user = fake()->boolean() ? user_class()::inRandomOrder()->first() : null;

        if (! $user || Author::where('name', $user->name)->exists()) {
            $name = fake()->boolean() ? fake()->name() : fake()->userName();
        } else {
            $name = $user->name;
        }
        $name .= fake()->boolean() ? fake()->numberBetween(1, 1000) : '';

        // If we have a user, we use their name.
        if ($user) {
            return $definition + [
                'name' => $name,
                'user_id' => $user->id,
            ];
        }

        // If we don't have a user, we generate a unique name.
        $name = fake()->name();
        $counter = 1;

        while (Author::where('name', $name)->exists()) {
            $name = fake()->name() . ' ' . $counter;
            $counter++;
        }

        return $definition + [
            'name' => $name,
            'user_id' => null,
        ];
    }

    #[Override]
    public function configure(): self
    {
        /** @param Model&HasDynamicContents $model */
        return $this->afterMaking(function (Author $model): void {
            $this->fillContents($model, [
                'public_email' => fake()->boolean() ? fake()->unique()->email() : ($model->user ? $model->user->email : null),
            ]);
        });
    }
}
