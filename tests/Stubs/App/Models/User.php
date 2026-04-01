<?php

declare(strict_types=1);

namespace App\Models;

use Modules\Cms\Tests\Support\User as CmsTestUser;

/**
 * App user stub so Filament/CMS code type-hinting {@see User} resolves in submodule tests.
 */
class User extends CmsTestUser {}
