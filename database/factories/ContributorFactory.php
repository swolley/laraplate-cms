<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use function user_class;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Helpers\HasDynamicContentFactory;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Models\Contributor;
use Modules\Core\Helpers\HasUniqueFactoryValues;
use Override;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Cms\Models\Contributor>
 */
final class ContributorFactory extends Factory
{
    use HasDynamicContentFactory, HasUniqueFactoryValues;

    /**
     * The name of the factory's corresponding model.
     */
    #[Override]
    protected $model = Contributor::class;

    protected EntityType $entityType = EntityType::CONTRIBUTORS;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $definition = $this->dynamicContentDefinition();
        $user = fake()->boolean() ? user_class()::query()->inRandomOrder()->first() : null;

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
        return $this->afterMaking(function (Contributor $model): void {
            $this->fillDynamicContents($model, [
                'public_email' => fake()->boolean() ? fake()->unique()->email() : ($model->user ? $model->user->email : null),
            ]);
        });
    }
}
