<?php

declare(strict_types=1);

use Modules\Cms\Models\Author;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Location;
use Modules\Cms\Models\Tag;

test('content creation workflow structure', function (): void {
    // 1. Test Content model structure
    $reflection = new ReflectionClass(Content::class);
    expect($reflection->hasMethod('entity'))->toBeTrue();
    expect($reflection->hasMethod('authors'))->toBeTrue();
    expect($reflection->hasMethod('categories'))->toBeTrue();
    expect($reflection->hasMethod('tags'))->toBeTrue();
    expect($reflection->hasMethod('locations'))->toBeTrue();

    // 2. Test Content model traits
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('HasDynamicContents');
    expect($source)->toContain('HasMultimedia');
    expect($source)->toContain('HasSlug');
    expect($source)->toContain('HasTags');
    expect($source)->toContain('HasApprovals');
    expect($source)->toContain('HasVersions');

    // 3. Test Content model relationships
    expect($source)->toContain('public function authors()');
    expect($source)->toContain('public function categories()');
    expect($source)->toContain('public function locations()');
    expect($source)->toContain('public function toSearchableArray()');
});

test('entity and field management workflow structure', function (): void {
    // 1. Test Entity model structure
    $reflection = new ReflectionClass(Entity::class);
    expect($reflection->hasMethod('presets'))->toBeTrue();
    expect($reflection->hasMethod('contents'))->toBeTrue();

    // 2. Test Entity model traits
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('HasPath');
    expect($source)->toContain('HasSlug');
    expect($source)->toContain('HasValidations');
    expect($source)->toContain('HasCache');

    // 3. Test Entity model relationships
    expect($source)->toContain('public function presets()');
    expect($source)->toContain('public function contents()');

    // 4. Test Field model structure
    $reflection = new ReflectionClass(Field::class);
    expect($reflection->hasMethod('presets'))->toBeTrue();

    // 5. Test Field model relationships
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('public function presets()');
    expect($source)->toContain('belongsToMany');
});

test('content model creation workflow structure', function (): void {
    // 1. Test CreateContentModelCommand structure
    $reflection = new ReflectionClass(Modules\Cms\Console\CreateContentModelCommand::class);
    expect($reflection->hasMethod('handle'))->toBeTrue();

    // 2. Test command signature
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('model:make-content-model');
    expect($source)->toContain('Create a new content model');

    // 3. Test command uses confirm method
    expect($source)->toContain('$this->confirm');
    expect($source)->toContain('$this->call');

    // 4. Test command uses file operations
    expect($source)->toContain('file_exists');
    expect($source)->toContain('file_put_contents');

    // 5. Test Event model structure
    $reflection = new ReflectionClass(Modules\Cms\Models\Contents\Event::class);
    expect($reflection->hasMethod('authors'))->toBeTrue();
    expect($reflection->hasMethod('categories'))->toBeTrue();
});

test('location and geocoding workflow structure', function (): void {
    // 1. Test Location model structure
    $reflection = new ReflectionClass(Location::class);
    expect($reflection->hasMethod('contents'))->toBeTrue();

    // 2. Test Location model relationships
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('public function contents()');
    expect($source)->toContain('belongsToMany');

    // 3. Test LocationsController structure
    $reflection = new ReflectionClass(Modules\Cms\Http\Controllers\LocationsController::class);
    expect($reflection->hasMethod('geocode'))->toBeTrue();

    // 4. Test controller methods
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('public function geocode');
    expect($source)->toContain('IGeocodingService');

    // 5. Test geocoding services
    $reflection = new ReflectionClass(Modules\Cms\Services\GoogleMapsService::class);
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();

    $reflection = new ReflectionClass(Modules\Cms\Services\NominatimService::class);
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

test('content categorization workflow structure', function (): void {
    // 1. Test Category model structure
    $reflection = new ReflectionClass(Category::class);
    expect($reflection->hasMethod('contents'))->toBeTrue();
    expect($reflection->hasMethod('entity'))->toBeTrue();

    // 2. Test Category model relationships
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('public function contents()');
    expect($source)->toContain('public function entity()');
    expect($source)->toContain('belongsToMany');
    expect($source)->toContain('belongsTo');

    // 3. Test Author model structure
    $reflection = new ReflectionClass(Author::class);
    expect($reflection->hasMethod('contents'))->toBeTrue();

    // 4. Test Author model relationships
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('public function contents()');
    expect($source)->toContain('belongsToMany');

    // 5. Test Tag model structure
    $reflection = new ReflectionClass(Tag::class);
    expect($reflection->hasMethod('getRules'))->toBeTrue();
    expect($reflection->hasMethod('getPath'))->toBeTrue();

    // 6. Test Tag model methods
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('public function getRules()');
    expect($source)->toContain('public function getPath()');
});

test('content publishing workflow structure', function (): void {
    // 1. Test Content model publishing methods
    $reflection = new ReflectionClass(Content::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('HasDynamicContents');
    expect($source)->toContain('HasApprovals');
    expect($source)->toContain('HasVersions');

    // 2. Test Content model soft deletes
    expect($source)->toContain('SoftDeletes');
    expect($source)->toContain('HasLocks');

    // 3. Test Content model search functionality
    expect($source)->toContain('Searchable');
    expect($source)->toContain('HasDynamicContents');

    // 4. Test Content model media handling
    expect($source)->toContain('HasMultimedia');
    expect($source)->toContain('HasMedia');

    // 5. Test Content model sorting
    expect($source)->toContain('Sortable');
    expect($source)->toContain('SortableTrait');

    // 6. Test Content model validation
    expect($source)->toContain('HasValidations');
    expect($source)->toContain('HasValidity');
});

test('content database seeder workflow structure', function (): void {
    // 1. Test CMS seeder structure
    $reflection = new ReflectionClass(Modules\Cms\Database\Seeders\CmsDatabaseSeeder::class);
    expect($reflection->hasMethod('run'))->toBeTrue();

    // 2. Test seeder populates basic data
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('Field::class');
    expect($source)->toContain('Entity::class');
});

test('content migration workflow structure', function (): void {
    // 1. Test migration files exist
    $migrationFiles = glob('/srv/http/laraplate/Modules/Cms/database/migrations/*.php');
    expect($migrationFiles)->not->toBeEmpty();

    // 2. Test migration files have correct structure
    foreach ($migrationFiles as $file) {
        $source = file_get_contents($file);
        expect($source)->toContain('use Illuminate\\Database\\Migrations\\Migration');
        expect($source)->toContain('use Illuminate\\Database\\Schema\\Blueprint');
        expect($source)->toContain('use Illuminate\\Support\\Facades\\Schema');
    }
});

test('content filament resources workflow structure', function (): void {
    // 1. Test ContentResource structure
    $reflection = new ReflectionClass(Modules\Cms\Filament\Resources\Contents\ContentResource::class);
    expect($reflection->hasMethod('form'))->toBeTrue();
    expect($reflection->hasMethod('table'))->toBeTrue();

    // 2. Test resource methods
    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('public static function form');
    expect($source)->toContain('public static function table');
});
