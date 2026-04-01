<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$stub_files = [
    __DIR__ . '/Stubs/Core/Helpers/HasCommandUtils.php',
    __DIR__ . '/Stubs/Core/Helpers/HasActivation.php',
    __DIR__ . '/Stubs/Core/Helpers/HasApprovals.php',
    __DIR__ . '/Stubs/Core/Helpers/HasValidations.php',
    __DIR__ . '/Stubs/Core/Helpers/HasValidity.php',
    __DIR__ . '/Stubs/Core/Helpers/HasVersions.php',
    __DIR__ . '/Stubs/Core/Helpers/HasTranslations.php',
    __DIR__ . '/Stubs/Core/Helpers/SoftDeletes.php',
    __DIR__ . '/Stubs/Core/Helpers/SortableTrait.php',
    __DIR__ . '/Stubs/Core/Helpers/LocaleContext.php',
    __DIR__ . '/Stubs/Core/Helpers/ResponseBuilder.php',
    __DIR__ . '/Stubs/Core/Helpers/MigrateUtils.php',
    __DIR__ . '/Stubs/Core/Helpers/HasUniqueFactoryValues.php',
    __DIR__ . '/Stubs/Core/Casts/FilterOperator.php',
    __DIR__ . '/Stubs/Core/Casts/WhereClause.php',
    __DIR__ . '/Stubs/Core/Cache/HasCache.php',
    __DIR__ . '/Stubs/Core/Locking/HasOptimisticLocking.php',
    __DIR__ . '/Stubs/Core/Locking/Traits/HasLocks.php',
    __DIR__ . '/Stubs/Core/Overrides/Command.php',
    __DIR__ . '/Stubs/Core/Overrides/ModuleServiceProvider.php',
    __DIR__ . '/Stubs/Core/Overrides/RouteServiceProvider.php',
    __DIR__ . '/Stubs/Core/Http/Requests/ListRequest.php',
    __DIR__ . '/Stubs/Core/Services/Translation/Definitions/ITranslated.php',
    __DIR__ . '/Stubs/Core/Search/Traits/Searchable.php',
    __DIR__ . '/Stubs/Core/Search/Schema/SchemaDefinition.php',
    __DIR__ . '/Stubs/Core/Search/Schema/FieldDefinition.php',
    __DIR__ . '/Stubs/Core/Search/Schema/FieldType.php',
    __DIR__ . '/Stubs/Core/Search/Schema/IndexType.php',
    __DIR__ . '/Stubs/Spatial/Objects/Point.php',
    __DIR__ . '/Stubs/Spatial/Objects/Polygon.php',
    __DIR__ . '/Stubs/Spatial/Traits/HasSpatial.php',
];

foreach ($stub_files as $stub_file) {
    require_once $stub_file;
}
