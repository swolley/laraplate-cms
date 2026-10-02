<?php

declare(strict_types=1);

use Modules\CMS\Filament\Utils\HasRecords as CmsHasRecords;
use Modules\CMS\Filament\Utils\HasTable as CmsHasTable;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Filament\FilamentTraitResolver;
use Modules\Core\Filament\Utils\HasForm as CoreHasForm;

uses(TestCase::class);

it('gives generated CMS classes the CMS traits, and Core\'s where CMS defines none', function (): void {
    expect(FilamentTraitResolver::resolve('Modules\\CMS\\Filament\\Resources\\Tags\\Tables\\TagsTable', 'HasTable'))
        ->toBe(CmsHasTable::class)
        ->and(FilamentTraitResolver::resolve('Modules\\CMS\\Filament\\Resources\\Tags\\Pages\\ListTags', 'HasRecords'))
        ->toBe(CmsHasRecords::class)
        ->and(FilamentTraitResolver::resolve('Modules\\CMS\\Filament\\Resources\\Tags\\Schemas\\TagForm', 'HasForm'))
        ->toBe(CoreHasForm::class);
});
