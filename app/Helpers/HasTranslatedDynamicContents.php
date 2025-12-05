<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Modules\Core\Helpers\HasTranslations;
use Override;

trait HasTranslatedDynamicContents
{
    // region Traits
    use HasDynamicContents {
        HasDynamicContents::getRules as private getRulesDynamicContents;
        HasDynamicContents::toArray as private dynamicContentsToArray;
        HasDynamicContents::__get as private __dynamicContentsGet;
        HasDynamicContents::__set as private __dynamicContentsSet;
        HasDynamicContents::setAttribute as private dynamicContentsSetAttribute;
        HasDynamicContents::getComponentsAttribute as private getComponentsAttributeDynamicContents;
        HasDynamicContents::setComponentsAttribute as private setComponentsAttributeDynamicContents;
    }
    use HasTranslations;
    use HasTranslations {
        HasTranslations::toArray as private translationsToArray;
        HasTranslations::__get as private __translationsGet;
        HasTranslations::__set as private __translationsSet;
        HasTranslations::setAttribute as private translationsSetAttribute;
    }
    // endregion Traits

    /**
     * Handle __get to merge translations and dynamic contents.
     */
    public function __get($key)
    {
        if ($this->isTranslatableField($key)) {
            return $this->__translationsGet($key);
        }

        // Then check dynamic contents
        return $this->__dynamicContentsGet($key);
    }

    /**
     * Handle __set to merge translations and dynamic contents.
     */
    public function __set($key, $value): void
    {
        if ($this->isTranslatableField($key)) {
            $this->__translationsSet($key, $value);

            return;
        }

        // Then check dynamic contents
        $this->__dynamicContentsSet($key, $value);
    }

    public function toArray(): array
    {
        $parsed = parent::toArray() ?? $this->attributesToArray();
        $array = array_merge($parsed, $this->dynamicContentsToArray($parsed), $this->translationsToArray($parsed));

        // Merge translatable fields from translation (trasparente)
        $translation = $this->getRelationValue('translation');

        if ($translation) {
            foreach ($this->getTranslatableFields() as $field) {
                if (isset($translation->{$field})) {
                    $array[$field] = $translation->{$field};
                }
            }
        }

        return $array;
    }

    /**
     * Override setAttribute to handle translatable fields.
     */
    public function setAttribute($key, $value)
    {
        // Check if it's a translatable field (cache the result to avoid recursion)
        $translatable_fields = $this->getTranslatableFields();

        if (in_array($key, $translatable_fields, true)) {
            return $this->translationsSetAttribute($key, $value);
        }

        $components = $this->getComponentsAttribute();

        if (array_key_exists($key, $components)) {
            return $this->dynamicContentsSetAttribute($key, $value);
        }

        return parent::setAttribute($key, $value);
    }

    protected function getComponentsAttribute(): array
    {
        $raw_components = $this->getTranslatableFieldValue('components');

        return $this->mergeComponentsValues($raw_components ?? []);
    }

    // protected function setComponentsAttribute(array $components): void
    // {
    //     $this->setTranslatableFieldValue('components', $this->mergeComponentsValues($components));
    // }
}
