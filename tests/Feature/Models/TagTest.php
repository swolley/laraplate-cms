<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Tag;
use Modules\Cms\Models\Translations\TagTranslation;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (
        ! method_exists(Tag::class, 'determineOrderColumnName')
        || ! Schema::hasTable('tags_translations')
    ) {
        $this->markTestSkipped('Tag integration features require full Core runtime.');
    }

    setupCmsEntities();
});

it('can be created with factory', function (): void {
    $tag = Tag::factory()->create();

    expect($tag)->toBeInstanceOf(Tag::class);
    expect($tag->id)->not->toBeNull();
});

it('has translatable attributes', function (): void {
    $tag = Tag::factory()->create();

    // Set translation for default locale
    $default_locale = config('app.locale');
    $tag->setTranslation($default_locale, [
        'name' => 'Laravel',
        'slug' => 'laravel',
    ]);
    $tag->save();

    expect($tag->name)->toBe('Laravel');
    expect($tag->slug)->toBe('laravel');
});

it('has hidden attributes', function (): void {
    $tag = Tag::factory()->create();
    $tagArray = $tag->toArray();

    expect($tagArray)->not->toHaveKey('order_column');
    expect($tagArray)->not->toHaveKey('created_at');
    expect($tagArray)->not->toHaveKey('updated_at');
});

it('belongs to many contents', function (): void {
    $tag = Tag::factory()->create();
    $content1 = Content::factory()->create();
    $content2 = Content::factory()->create();

    $tag->contents()->attach([$content1->id, $content2->id]);

    expect($tag->contents)->toHaveCount(2);
});

it('has slug trait', function (): void {
    expect(method_exists(TagTranslation::class, 'generateSlug'))->toBeTrue();
});

it('has path trait', function (): void {
    expect(method_exists(Tag::class, 'getPath'))->toBeTrue();
});

it('has sortable trait', function (): void {
    expect(method_exists(Tag::class, 'moveToEnd'))->toBeTrue();
});

it('has soft deletes trait', function (): void {
    $tag = Tag::factory()->create();
    $tag->delete();

    expect($tag->trashed())->toBeTrue();
    expect(Tag::withTrashed()->find($tag->id))->not->toBeNull();
});

it('has validations trait', function (): void {
    expect(method_exists(Tag::class, 'getRules'))->toBeTrue();
});

it('has translations trait', function (): void {
    $tag = Tag::factory()->create();

    expect(method_exists($tag, 'translations'))->toBeTrue();
    expect(method_exists($tag, 'translation'))->toBeTrue();
    expect(method_exists($tag, 'getTranslation'))->toBeTrue();
    expect(method_exists($tag, 'setTranslation'))->toBeTrue();
    expect(method_exists($tag, 'hasTranslation'))->toBeTrue();
    expect(method_exists($tag, 'getTranslatableFields'))->toBeTrue();
});

it('tag translation resolves inverse tag relation', function (): void {
    $tag = Tag::factory()->create();
    $locale = config('app.locale');
    $tag->setTranslation($locale, [
        'name' => 'Inverse Relation',
        'slug' => 'inverse-relation',
    ]);
    $tag->save();

    $translation = $tag->translations()->first();

    expect($translation)->toBeInstanceOf(TagTranslation::class)
        ->and($translation->tag->is($tag))->toBeTrue();
});

it('can find or create tags', function (): void {
    $tag = Tag::findOrCreateFromString('Laravel');

    expect($tag)->toBeInstanceOf(Tag::class);
    expect($tag->name)->toBe('Laravel');

    // Should return the same tag if called again
    $sameTag = Tag::findOrCreateFromString('Laravel');
    expect($sameTag->id)->toBe($tag->id);
});

it('can find or create multiple tags', function (): void {
    $tags = Tag::findOrCreate(['Laravel', 'PHP', 'Web Development']);

    expect($tags)->toHaveCount(3);
    expect($tags->pluck('name')->toArray())->toContain('Laravel', 'PHP', 'Web Development');
});

it('can be created with specific translation attributes', function (): void {
    $tag = Tag::factory()->create(['type' => 'programming']);
    $tag->updateQuietly(['order_column' => 2]);
    $default_locale = config('app.locale');
    $tag->setTranslation($default_locale, [
        'name' => 'JavaScript',
        'slug' => 'javascript',
    ]);
    $tag->save();

    expect($tag->name)->toBe('JavaScript');
    expect($tag->slug)->toBe('javascript');
    expect($tag->type)->toBe('programming');
    expect($tag->order_column)->toBe(2);
});

it('can be found by name through translation', function (): void {
    $tag = Tag::factory()->create();
    $default_locale = config('app.locale');
    $tag->setTranslation($default_locale, [
        'name' => 'Unique Tag',
        'slug' => 'unique-tag',
    ]);
    $tag->save();

    $foundTag = Tag::query()->whereHas('translations', static function ($q): void {
        $q->where('name', 'Unique Tag');
    })->first();

    expect($foundTag->id)->toBe($tag->id);
    expect($foundTag->name)->toBe('Unique Tag');
});

it('can be found by slug through translation', function (): void {
    $tag = Tag::factory()->create();
    $default_locale = config('app.locale');
    $tag->setTranslation($default_locale, [
        'name' => 'Test Tag',
        'slug' => 'unique-slug',
    ]);
    $tag->save();

    $foundTag = Tag::query()->whereHas('translations', static function ($q): void {
        $q->where('slug', 'unique-slug');
    })->first();

    expect($foundTag->id)->toBe($tag->id);
    expect($foundTag->slug)->toBe('unique-slug');
});

it('can be found by type', function (): void {
    $tag = Tag::factory()->create(['type' => 'technology']);

    $foundTag = Tag::query()->where('type', 'technology')->first();

    expect($foundTag->id)->toBe($tag->id);
});

it('has proper timestamps', function (): void {
    $tag = Tag::factory()->create();

    expect($tag->created_at)->toBeInstanceOf(CarbonImmutable::class);
    expect($tag->updated_at)->toBeInstanceOf(Carbon::class);
});

it('can be serialized to array with translations', function (): void {
    $tag = Tag::factory()->create(['type' => 'test']);
    $default_locale = config('app.locale');
    $tag->setTranslation($default_locale, [
        'name' => 'Test Tag',
        'slug' => 'test-tag',
    ]);
    $tag->save();

    $tagArray = $tag->toArray();

    expect($tagArray)->toHaveKey('id');
    expect($tagArray)->toHaveKey('name');
    expect($tagArray)->toHaveKey('slug');
    expect($tagArray)->toHaveKey('type');
    expect($tagArray['name'])->toBe('Test Tag');
    expect($tagArray['slug'])->toBe('test-tag');
    expect($tagArray['type'])->toBe('test');
});

it('can be restored after soft delete', function (): void {
    $tag = Tag::factory()->create();
    $tag->delete();

    expect($tag->trashed())->toBeTrue();

    $tag->restore();

    expect($tag->trashed())->toBeFalse();
});

it('can be permanently deleted', function (): void {
    $tag = Tag::factory()->create();
    $tagId = $tag->id;

    $tag->forceDelete();

    expect(Tag::withTrashed()->find($tagId))->toBeNull();
});

it('can be sorted by order column', function (): void {
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();
    $tag3 = Tag::factory()->create();
    $tag1->updateQuietly(['order_column' => 2]);
    $tag2->updateQuietly(['order_column' => 1]);
    $tag3->updateQuietly(['order_column' => 3]);

    $sortedTags = Tag::ordered()->get();

    expect($sortedTags->first()->id)->toBe($tag2->id);
    expect($sortedTags->last()->id)->toBe($tag3->id);
});

it('can be moved in order', function (): void {
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();
    $tag3 = Tag::factory()->create();
    $tag1->updateQuietly(['order_column' => 1]);
    $tag2->updateQuietly(['order_column' => 2]);
    $tag3->updateQuietly(['order_column' => 3]);

    $tag1->moveToEnd();

    expect($tag1->fresh()->order_column)->toBe(3);
    expect($tag2->fresh()->order_column)->toBe(1);
    expect($tag3->fresh()->order_column)->toBe(2);
});

it('find or create with array returns a collection of tags', function (): void {
    $tags = Tag::findOrCreate(['Alpha', 'Beta']);

    expect($tags)->toHaveCount(2)
        ->and($tags->first())->toBeInstanceOf(Tag::class);
});

it('get with type returns tags for that type', function (): void {
    Tag::factory()->create(['type' => 'news']);

    $list = Tag::getWithType('news');

    expect($list)->not->toBeEmpty();
    expect($list->first()->type)->toBe('news');
});

it('find from string of any type matches translation name', function (): void {
    $tag = Tag::factory()->create();
    $locale = config('app.locale');
    $tag->setTranslation($locale, [
        'name' => 'GlobUnique',
        'slug' => 'glob-unique',
    ]);
    $tag->save();

    $found = Tag::findFromStringOfAnyType('GlobUnique');

    expect($found->pluck('id')->contains($tag->id))->toBeTrue();
});

it('get types lists distinct type values', function (): void {
    Tag::factory()->create(['type' => 'alpha-type']);

    $types = Tag::getTypes();

    expect($types->contains('alpha-type'))->toBeTrue();
});

it('with type scope restricts by type when argument is not null', function (): void {
    Tag::factory()->create(['type' => 'scope-me']);

    expect(Tag::query()->withType('scope-me')->count())->toBeGreaterThanOrEqual(1);
});

it('exposes merged validation rules', function (): void {
    $tag = Tag::factory()->create();
    $rules = $tag->getRules();

    expect($rules)->toHaveKey('create')
        ->and($rules)->toHaveKey('update')
        ->and($rules['create'])->toHaveKey('translations');
});

it('slug placeholders include name token', function (): void {
    $tag = Tag::factory()->create();
    $method = new ReflectionMethod(Tag::class, 'slugPlaceholders');
    $method->setAccessible(true);

    expect($method->invoke($tag))->toBe(['{name}']);
});

it('containing scope matches translation name substring for default locale', function (): void {
    $locale = config('app.locale');
    $tag_match = Tag::factory()->create();
    $tag_match->setTranslation($locale, [
        'name' => 'Unique Beta String',
        'slug' => 'unique-beta-string',
    ]);
    $tag_match->save();

    $tag_other = Tag::factory()->create();
    $tag_other->setTranslation($locale, [
        'name' => 'Other Name',
        'slug' => 'other-name',
    ]);
    $tag_other->save();

    $ids = Tag::query()->containing('beta')->pluck('id')->all();

    expect($ids)->toContain($tag_match->id)
        ->and($ids)->not->toContain($tag_other->id);
});
