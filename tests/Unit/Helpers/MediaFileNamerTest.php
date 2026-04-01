<?php

declare(strict_types=1);

use Modules\Cms\Helpers\MediaFileNamer;
use Modules\Cms\Tests\TestCase;
use Spatie\MediaLibrary\Conversions\Conversion;

uses(TestCase::class);

it('builds short thumb conversion names when conversion name contains thumb-', function (): void {
    $conversion = Mockery::mock(Conversion::class);
    $conversion->shouldReceive('getName')->andReturn('thumb-high');

    $namer = new MediaFileNamer;

    expect($namer->conversionFileName('my-photo.jpg', $conversion))->toBe('my-photo-high');
});

it('uses substring after first hyphen when name contains thumb-', function (): void {
    $conversion = Mockery::mock(Conversion::class);
    $conversion->shouldReceive('getName')->andReturn('video_thumb-mid');

    $namer = new MediaFileNamer;

    expect($namer->conversionFileName('clip.mp4', $conversion))->toBe('clip-mid');
});

it('delegates to default file namer when conversion name does not contain thumb-', function (): void {
    $conversion = Mockery::mock(Conversion::class);
    $conversion->shouldReceive('getName')->andReturn('preview');

    $namer = new MediaFileNamer;

    expect($namer->conversionFileName('document.pdf', $conversion))->toBe('document-preview');
});
