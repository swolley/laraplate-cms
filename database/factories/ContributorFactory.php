<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Factories;

use function user_class;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\CMS\Models\Contributor;
use Modules\Core\Database\Factories\Concerns\HasDynamicContentFactory;
use Modules\Core\Database\Factories\Concerns\HasUniqueFactoryValues;
use Modules\Core\Models\Concerns\HasDynamicContents;
use Override;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\CMS\Models\Contributor>
 */
final class ContributorFactory extends Factory
{
    use HasDynamicContentFactory, HasUniqueFactoryValues;

    /**
     * The name of the factory's corresponding model.
     */
    #[Override]
    protected $model = Contributor::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $definition = $this->dynamicContentDefinition();
        $user = fake()->boolean() ? user_class()::query()->inRandomOrder()->first() : null;

        // uniqueValue checks the DB; PID keeps parallel BatchSeeder forks (which
        // inherit the same Faker unique/RNG state) from generating identical names.
        $name = $this->uniqueValue(
            static function () use ($user): string {
                $base_name = $user !== null
                    ? $user->name
                    : (fake()->boolean() ? fake()->name() : fake()->userName());

                return $base_name . '-' . getmypid() . '-' . fake()->unique()->numerify('########');
            },
            $this->model,
            'name',
        );

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
        })->afterCreating(function (Contributor $model): void {
            // slug is a translated field, so a Contributor without a current-locale
            // translation is invisible to the LocaleScope global scope. Seed one (as
            // CategoryFactory does) so factory-made contributors are queryable.
            $locale = (string) config('app.locale');

            if (! $model->translations()->where('locale', $locale)->exists()) {
                $model->setTranslation($locale, [
                    'slug' => Str::slug((string) $model->name),
                    'components' => [],
                ]);
            }
        });
    }
}
