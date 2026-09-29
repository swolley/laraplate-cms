<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Services\ContentOwnerAuthorizer;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Media;
use Modules\Core\Search\OwnerAuthorizerRegistry;

uses(TestCase::class, RefreshDatabase::class);

function contentOwnedMedia(Content $content): Media
{
    $media = new Media();
    $media->forceFill([
        'collection_name' => 'default',
        'name' => 'photo',
        'file_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 123,
        'model_type' => $content->getMorphClass(),
        'model_id' => $content->getKey(),
        'custom_properties' => [],
        'manipulations' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
    ]);
    $media->saveQuietly();

    return $media;
}

it('registers the content owner authorizer', function (): void {
    expect(app(OwnerAuthorizerRegistry::class)->for(Content::class))->toBeInstanceOf(ContentOwnerAuthorizer::class);
});

it('keeps only the media of contents that are currently valid', function (): void {
    config()->set('core.media.search_visibility', 'owner');
    setupCMSEntities([EntityType::Contents]);

    $live = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $expired = Content::factory()->create(['valid_from' => now()->subDays(10), 'valid_to' => now()->subDay()]);
    $kept = contentOwnedMedia($live);
    $dropped = contentOwnedMedia($expired);

    $ids = (new Media())->authorizeSearchRehydration(Media::query()->whereKey([$kept->id, $dropped->id]))->pluck('id')->all();

    expect($ids)->toBe([$kept->id]);
});
