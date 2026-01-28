<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Modules\Cms\Helpers\HasSlug;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('can get slug fields', function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    $fields = $trait::slugPlaceholders();

    expect($fields)->toBeArray();
    expect($fields)->toContain('{name}');
});

it('can get slug values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public int $id = 1;

        public string $name = 'Test Name';

        public static function slugPlaceholders(): array
        {
            return ['{id}', '{name}'];
        }
    };

    $reflection = new ReflectionClass($trait);

    $fields = $trait::slugPlaceholders();
    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(count($fields));
    expect($values[0])->toBe('1');
    expect($values[1])->toBe('test-name');
});

it('traits values are always valid slug values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public string $name = "I'm a test name with special characters like ', / and #";
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);
    $slug = $trait->generateSlug();

    expect($values)->toBeArray();
    expect($values)->toHaveCount(1);
    expect($values[0])->toBe('im-a-test-name-with-special-characters-like-and');
    expect($slug)->toBe('im-a-test-name-with-special-characters-like-and');
});

it('trait methods are accessible', function (): void {
    $trait = new class extends Model
    {
        use HasSlug;

        protected $table = 'test_table';
    };

    expect(method_exists($trait, 'generateSlug'))->toBeTrue();
    expect(method_exists($trait, 'slug'))->toBeTrue();
    expect(method_exists($trait, 'slugPlaceholders'))->toBeTrue();
});

it('trait can handle string values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public string $name = 'Test Name';
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(1);
    expect($values[0])->toBe('test-name');
});

it('trait can handle callable values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public static function slugPlaceholders(): array
        {
            return [fn ($model) => $model->name()];
        }

        public function name(): string
        {
            return 'Test Name';
        }
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(1);
    expect($values[0])->toBe('test-name');
});

it('trait can handle model accessors', function (): void {
    $trait = new class extends Model
    {
        use HasSlug;

        public static function slugPlaceholders(): array
        {
            return ['{name}'];
        }

        public function getNameAttribute(): string
        {
            return 'Test Name';
        }
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(1);
    expect($values[0])->toBe('test-name');
});

it('trait can handle static values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public static function slugPlaceholders(): array
        {
            return ['static-value'];
        }
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(1);
    expect($values[0])->toBe('static-value');
});

it('trait can handle null and empty values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public static function slugPlaceholders(): array
        {
            return ['{empty}', '{null}'];
        }
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(2);
    expect($values[0])->toBe('{empty}');
    expect($values[1])->toBe('{null}');
});

it('trait can handle format string values by native functions', function (): void {
    $trait = new class
    {
        use HasSlug;

        public string $name = 'test name';

        public static function slugPlaceholders(): array
        {
            return ['{name:strrev}'];
        }
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(1);
    expect($values[0])->toBe('eman-tset');
});

it('trait can handle formatted string and number values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public float $id = 1.234567890;

        public string $name = 'test name';

        public static function slugPlaceholders(): array
        {
            return ['{id:round,2}', '{name:%s with some other text}'];
        }
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(2);
    expect($values[0])->toBe('1-23');
    expect($values[1])->toBe('test-name-with-some-other-text');
});

it('trait can handle array and object values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public array $tags = ['test', 'tags'];

        public array $categories = [];

        public array $authors = ['test', 'authors'];

        public function __construct()
        {
            $first_category = [
                'id' => 1,
                'name' => 'category 1',
            ];
            $second_category = new stdClass();
            $second_category->id = 2;
            $second_category->name = 'category 2';

            $this->categories = [
                $first_category,
                $second_category,
            ];
        }

        public static function slugPlaceholders(): array
        {
            return ['{tags.0}', '{categories.*.name}', '{authors}'];
        }
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(3);
    expect($values[0])->toBe('test');
    expect($values[1])->toBe('category-1');
    expect($values[2])->toBe('test');
});

it('trait can handle date and time values', function (): void {
    $trait = new class
    {
        use HasSlug;

        public DateTimeInterface $created_at;

        public function __construct()
        {
            $this->created_at = Date::parse('2021-01-01 12:00:00');
        }

        public static function slugPlaceholders(): array
        {
            return ['{created_at:Y-m-d}', '{created_at:H-i-s}', '{created_at}'];
        }
    };

    $reflection = new ReflectionClass($trait);

    $values = $reflection->getMethod('slugValues')->invoke($trait);

    expect($values)->toBeArray();
    expect($values)->toHaveCount(3);
    expect($values[0])->toBe('2021-01-01');
    expect($values[1])->toBe('12-00-00');
    expect($values[2])->toBe('2021-01-01');
});
