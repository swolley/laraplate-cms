<?php

declare(strict_types=1);

namespace Modules\Cms\Tests\Support;

require_once __DIR__ . '/Database/Factories/UserFactory.php';

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Cms\Tests\Support\Database\Factories\UserFactory;
use Override;

final class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * @var array<int, string>
     */
    #[Override]
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var array<int, string>
     */
    #[Override]
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'string',
            'password' => 'string',
        ];
    }
}
