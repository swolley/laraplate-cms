<?php

declare(strict_types=1);

namespace Modules\CMS\Enums;

enum CMSTables: string
{
    // cms models
    case Tags = 'cms_tags';
    case Taggables = 'cms_taggables';
    case Contributors = 'cms_contributors';
    case Locations = 'cms_locations';
    case Contents = 'cms_contents';
    case Comments = 'cms_comments';
    case ContentRatings = 'cms_contents_ratings';

    // generic or vendors models
    case Media = 'vend_media';

    // translations
    case ContentsTranslations = 'cms_contents_translations';
    case ContributorsTranslations = 'cms_contributors_translations';
    case TagsTranslations = 'cms_tags_translations';
    case CommentsTranslations = 'cms_comments_translations';

    // pivots
    case Categorizables = 'cms_categorizables';
    case Locatables = 'cms_locatables';
    case Relatables = 'cms_relatables';
    case Contributables = 'cms_contributables';
}
