<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Modules\Cms\Database\Factories\AuthorFactory;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Helpers\HasMultimedia;
use Modules\Cms\Helpers\HasTags;
use Modules\Cms\Models\Pivot\Authorable;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Modules\Core\Overrides\ComposhipsModel;
use Override;
use Spatie\MediaLibrary\HasMedia as IMediable;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * @mixin IdeHelperAuthor
 */
final class Author extends ComposhipsModel implements IMediable
{
    use HasDynamicContents, HasFactory, HasMultimedia, HasTags, HasValidations, HasVersions, SoftDeletes {
        HasValidations::getRules as protected getRulesTrait;
        HasDynamicContents::getRules as protected getRulesDynamicContents;
        HasDynamicContents::__get as protected dynamicContentsGet;
        HasDynamicContents::__set as protected dynamicContentsSet;
    }

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'user_id',
        'user',
        'created_at',
        'updated_at',
    ];

    private ?User $tempUser = null;

    // Magic getter for user attributes
    #[Override]
    public function __get($key)
    {
        if (in_array($key, new User()->getFillable(), true)) {
            return $this->user?->{$key} ?? null;
        }

        return $this->dynamicContentsGet($key);
    }

    // Magic setter for user attributes
    #[Override]
    public function __set($key, $value): void
    {
        /** @var User|null $session_user */
        $session_user = Auth::user();
        $entity = new User();
        $table = $entity->getTable();
        $user_can_insert = $session_user && $session_user->can("{$table}.create");
        $user_can_update = $session_user && $session_user->can("{$table}.update");

        if (in_array($key, $entity->getFillable(), true) && ($user_can_insert || $user_can_update)) {
            throw_if(! $this->user && ! $user_can_insert, UnauthorizedException::class, ResponseAlias::HTTP_FORBIDDEN, "User cannot insert {$entity}");

            if (! $this->user && ! $this->tempUser instanceof User && $user_can_insert) {
                $this->tempUser = new User();
                $this->tempUser->{$key} = $value;
            } elseif ($user_can_update) {
                $this->user->{$key} = $value;
            }

            return;
        }

        $this->dynamicContentsSet($key, $value);
    }

    /**
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(user_class());
    }

    /**
     * The contents that belong to the author.
     *
     * @return BelongsToMany<Content>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'authorables')->using(Authorable::class)->withTimestamps();
    }

    // Save method to handle user creation/updating
    #[Override]
    public function save(array $options = []): bool
    {
        if ($this->tempUser instanceof User && $this->tempUser->isDirty()) {
            $this->tempUser->save();
            $this->user_id = $this->tempUser->id;
            $this->tempUser = null;
            $this->load('user');
        } elseif ($this->user && $this->user->isDirty()) {
            $this->user->save();
        }

        return parent::save($options);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $fields = $this->getRulesDynamicContents();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:authors,name'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:authors,name,' . $this->id],
        ]);

        return $rules;
    }

    protected static function boot(): void
    {
        parent::boot();

        self::addGlobalScope(fn (Builder $query) => $query->with('user'));
    }

    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function getCanLoginAttribute(): bool
    {
        return $this->user !== null || $this->tempUser instanceof User;
    }

    protected function getIsSignatureAttribute(): bool
    {
        return ! $this->getCanLoginAttribute();
    }

    private function isUserAttribute(): bool
    {
        return $this->getCanLoginAttribute();
    }
}
