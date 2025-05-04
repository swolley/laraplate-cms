<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Override;
use Illuminate\Support\Arr;
use Spatie\Image\Enums\Fit;
use Modules\Cms\Helpers\HasTags;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Modules\Cms\Models\Pivot\Authorable;
use Modules\Core\Helpers\HasValidations;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Core\Overrides\ComposhipsModel;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Modules\Cms\Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperAuthor
 */
final class Author extends ComposhipsModel
{
    use HasDynamicContents, HasFactory, HasTags, HasValidations, HasVersions, InteractsWithMedia, SoftDeletes {
        getRules as protected getRulesTrait;
        // HasDynamicContents::toArray as protected dynamicContentsToArray;
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

    protected $tempUser;

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
        $session_user = Auth::user();
        $entity = new User();
        $table = $entity->getTable();
        $user_can_insert = $session_user && $session_user->can("{$table}.create");
        $user_can_update = $session_user && $session_user->can("{$table}.update");

        if (in_array($key, $entity->getFillable(), true) && ($user_can_insert || $user_can_update)) {
            if (! $this->user && ! $user_can_insert) {
                throw new UnauthorizedException("User cannot insert {$entity}");
            }

            if (! $this->user && $this->tempUser === null && $user_can_insert) {
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
    public function save(array $options = [])
    {
        if ($this->tempUser !== null && $this->tempUser->isDirty()) {
            $this->tempUser->save();
            $this->user_id = $this->tempUser->id;
            $this->tempUser = null;
            $this->load('user');
        } elseif ($this->user && $this->user->isDirty()) {
            $this->user->save();
        }

        return parent::save($options);
    }

    // #[\Override]
    // public function toArray(): array
    // {
    //     $author = $this->dynamicContentsToArray();
    //     $user = $this->user ? Arr::except($this->user->toArray(), array_key_exists('name', $author) ? ['id', 'name'] : ['id']) : null;
    //     return $user ? array_merge($author, $user) : $author;
    // }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->singleFile();
        // $this->addMediaCollection('videos')
        // 	->extractVideoFrameAtSecond(2);
        // $this->addMediaCollection('audios');
        // $this->addMediaCollection('files');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('images'/* , 'videos' */)
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->fit(Fit::Fill, 300, 300);
    }

    // public function getPictureAttribute(): string
    // {
    //     return $this->getFirstMediaUrl('images', 'thumb');
    // }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:authors,name'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:authors,name,' . $this->id],
        ]);

        return $rules;
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
        return $this->user !== null || $this->tempUser !== null;
    }

    protected function getIsSignatureAttribute(): bool
    {
        return ! $this->getCanLoginAttribute();
    }

    protected function picture(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMediaUrl('images'),
            set: fn ($value) => $this->addMedia($value)->toMediaCollection('images'),
        );
    }
}
