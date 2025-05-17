<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Preset;
use Override;
use RuntimeException;

final class AuthorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Author::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $user = fake()->boolean() ? user_class()::inRandomOrder()->first() : null;

        if (! $user || Author::where('name', $user->name)->exists()) {
            $name = fake()->boolean() ? fake()->name() : fake()->userName();
        } else {
            $name = $user->name;
        }
        $name .= fake()->boolean() ? fake()->numberBetween(1, 1000) : '';

        // Se abbiamo un utente, usiamo il suo nome
        if ($user) {
            return [
                'name' => $name,
                'user_id' => $user->id,
            ];
        }

        // Se non abbiamo un utente, generiamo un nome unico
        $name = fake()->name();
        $counter = 1;

        while (Author::where('name', $name)->exists()) {
            $name = fake()->name() . ' ' . $counter;
            $counter++;
        }

        return [
            'name' => $name,
            'user_id' => null,
        ];
    }

    #[Override]
    public function configure(): static
    {
        return $this->afterMaking(function (Author $author): void {
            $entity = Entity::where('type', EntityType::AUTHORS)->inRandomOrder()->first();
            $preset = Preset::where('entity_id', $entity->id)->inRandomOrder()->first();

            if (! $preset) {
                throw new RuntimeException("No preset found for entity: {$author->entity->name}");
            }

            $author->entity_id = $entity->id;
            $author->preset_id = $preset->id;

            // set the components depending on the preset configured fields
            $author->components = $preset->fields->mapWithKeys(function (Field $field) use ($author) {
                $value = $field->pivot->default;

                if ($field->pivot->is_required || fake()->boolean()) {
                    $value = match ($field->type) {
                        FieldType::TEXTAREA => fake()->paragraphs(fake()->numberBetween(1, 3), true),
                        FieldType::TEXT => fake()->text(fake()->numberBetween(100, 255)),
                        FieldType::NUMBER => fake()->randomNumber(),
                        FieldType::EMAIL => fake()->boolean() ? fake()->unique()->email() : ($author->user ? $author->user->email : null),
                        FieldType::PHONE => fake()->boolean() ? fake()->unique()->phoneNumber() : null,
                        FieldType::URL => fake()->boolean() ? fake()->unique()->url() : null,
                        FieldType::JSON => [],
                        default => $value,
                    };
                }

                return [$field->name => $value];
            })->toArray();
        });
    }
}
