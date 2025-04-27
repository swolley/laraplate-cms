<?php

namespace Modules\Cms\Database\Factories;

use Modules\Cms\Models\Field;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Preset;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Casts\EntityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Cms\Models\Author::class;

    /**
     * Define the model's default state.
     */
    #[\Override]
    public function definition(): array
    {

        $user = fake()->boolean() ? user_class()::inRandomOrder()->first() : null;

        if (!$user || Author::where('name', $user->name)->exists()) {
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
        while (\Modules\Cms\Models\Author::where('name', $name)->exists()) {
            $name = fake()->name() . ' ' . $counter;
            $counter++;
        }

        return [
            'name' => $name,
            'user_id' => null,
        ];
    }

    #[\Override]
    public function configure(): static
    {
        return $this->afterMaking(function (Author $author) {
            $entity = Entity::where('type', EntityType::AUTHORS)->inRandomOrder()->first();
            $preset = Preset::where('entity_id', $entity->id)->inRandomOrder()->first();
            $author->public_email = fake()->boolean() ? fake()->unique()->email() : null;
            if (!$preset) {
                throw new \RuntimeException("No preset found for entity: {$author->entity->name}");
            }

            // set the components depending on the preset configured fields
            $author->components = $preset->fields->mapWithKeys(function (Field $field) use ($author) {
                $value = $field->default;
                if ($field->required || fake()->boolean()) {
                    $value = match ($field->type) {
                        FieldType::TEXTAREA => fake()->paragraphs(fake()->numberBetween(1, 3), true),
                        FieldType::TEXT => fake()->text(fake()->numberBetween(100, 255)),
                        FieldType::NUMBER => fake()->randomNumber(),
                        FieldType::EMAIL => fake()->boolean() ? fake()->unique()->email() : ($author->user ? $author->user->email : null),
                        FieldType::PHONE => fake()->boolean() ? fake()->unique()->phoneNumber() : null,
                        FieldType::URL => fake()->boolean() ? fake()->unique()->url() : null,
                        default => $field->default,
                    };
                }

                return [$field->name => $value];
            })->toArray();
        });
    }
}
