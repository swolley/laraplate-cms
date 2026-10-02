<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Core\Tests\Support\ForcedModelConfiguration;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => CoreDatabaseSeeder::class, '--no-interaction' => true]);
});

it('forces translation fallback on tags in code, which Core discovers as a forced configuration', function (): void {
    $forces_fallback = collect(ForcedModelConfiguration::cases())
        ->contains(fn (array $case): bool => $case['model'] === Tag::class
            && $case['property'] === 'translation_fallback_enabled'
            && $case['expected'] === true);

    expect($forces_fallback)->toBeTrue();
});
