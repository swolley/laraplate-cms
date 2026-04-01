<?php

declare(strict_types=1);

namespace Modules\Cms\Tests\Support;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Override;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    #[Override]
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}
