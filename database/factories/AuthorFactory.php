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

        if (!$user || Author::where('name', $user->name)->exists()) {
            $name = fake()->boolean() ? fake()->name() : fake()->userName();
        } else {
            $name = $user->name;
        }
        $name .= fake()->boolean() ? fake()->numberBetween(1, 1000) : '';
        $public_email = fake()->boolean() ? fake()->unique()->email() : ($user ? $user->email : null);

        // Se abbiamo un utente, usiamo il suo nome
        if ($user) {
            return [
                'name' => $name,
                'public_email' => $public_email,
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
