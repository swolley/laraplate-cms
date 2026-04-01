<?php

declare(strict_types=1);

namespace Modules\Cms\Tests\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Override;

/**
 * Test factory for the CMS standalone user stub.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Cms\Tests\Support\User>
 */
final class UserFactory extends Factory
{
    /**
     * @var class-string
     */
    #[Override]
    protected $model = \Modules\Cms\Tests\Support\User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}
