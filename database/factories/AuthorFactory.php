<?php

namespace Modules\Cms\Database\Factories;

use Modules\Cms\Models\Author;
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

        if ($user && Author::where('user_id', $user->id)->exists()) {
            $user = null;
        }

        // Se abbiamo un utente, usiamo il suo nome
        if ($user) {
            return [
                'name' => $user->name,
                'public_email' => fake()->boolean() ? fake()->unique()->email() : null,
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
            'public_email' => fake()->boolean() ? fake()->unique()->email() : null,
            'user_id' => null,
        ];
    }
}
