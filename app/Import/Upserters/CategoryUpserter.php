<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Upserters;

use Modules\CMS\Import\Dto\ImportCategoryDto;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportConnectionContext;
use Modules\CMS\Import\Support\ImportReferenceResolver;
use Modules\CMS\Models\Category;

final class CategoryUpserter
{
    public function __construct(
        private readonly EntityPresetResolver $entity_preset_resolver,
        private readonly ExternalReferenceLocator $locator,
        private readonly ImportReferenceResolver $reference_resolver,
        private readonly string $locale,
    ) {}

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public function participantModelClasses(): array
    {
        return [Category::class, \Modules\Core\Models\Translations\TaxonomyTranslation::class, \Modules\Core\Models\RecordOrigin::class];
    }

    public function upsert(ImportCategoryDto $dto, ?ImportConnectionContext $context = null): int
    {
        $context ??= new ImportConnectionContext(new Category);
        $category_model = $context->model(Category::class);
        $existing_id = $this->reference_resolver->resolve(
            'categories',
            Category::class,
            $dto->externalId,
            $dto->sourceType,
            $context,
        );

        $entity_id = $this->entity_preset_resolver->entityId($dto->entityName, $context);
        $presettable_id = $this->entity_preset_resolver->presettableId($dto->entityName, $dto->presetName, $context);
        $parent_id = $dto->parentExternalId !== null
            ? $this->reference_resolver->resolve(
                'categories',
                Category::class,
                $dto->parentExternalId,
                $dto->sourceType,
                $context,
            )
            : null;

        if ($existing_id !== null) {
            $category = $category_model->newQueryWithoutScopes()->with('presettable')->findOrFail($existing_id);
            $category->parent_id = $parent_id;
        } else {
            $category = $category_model->newInstance([
                'entity_id' => $entity_id,
                'presettable_id' => $presettable_id,
                'parent_id' => $parent_id,
            ]);
        }

        // A record that reappears in the source must be revived before it can be
        // updated: soft-deleted models reject updates ("Cannot update a softdeleted
        // model"). reviveInMemory() lets the save() below persist the restoration in
        // a single write. If the source still marks it deleted, it is re-deleted below.
        if ($category->exists && $category->trashed()) {
            $category->reviveInMemory();
        }

        $category->is_active = $dto->isActive;
        $category->order_column = $dto->orderColumn;
        $category->shared_components = $dto->sharedComponents;
        $category->save();

        $category->setTranslation($this->locale, [
            'name' => $dto->name,
            'slug' => $dto->slug,
            'components' => $dto->components,
        ]);
        $category->save();

        if ($dto->deletedAt !== null && ! $category->trashed()) {
            $category->delete();
        }

        $category_id = (int) $category->id;
        $this->reference_resolver->remember('categories', $dto->externalId, $category_id, $dto->sourceType, $context);

        $this->locator->register($category, $dto->sourceType, $dto->externalId);

        return $category_id;
    }
}
