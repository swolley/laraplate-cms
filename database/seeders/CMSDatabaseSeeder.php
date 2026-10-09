<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Seeders;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Preset;
use Modules\CMS\Models\Tag;
use Modules\Core\Casts\ActionEnum;
use Modules\Core\Casts\FieldType;
use Modules\Core\Casts\Filter;
use Modules\Core\Casts\FilterOperator;
use Modules\Core\Casts\FiltersGroup;
use Modules\Core\Casts\SettingTypeEnum;
use Modules\Core\Casts\WhereClause;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Core\Models\ACL;
use Modules\Core\Models\Field;
use Modules\Core\Models\Modification;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\Setting;
use Modules\Core\Overrides\Seeder;
use Modules\Core\Seeding\SeedReconciler;
use Modules\Core\Services\DynamicContentsService;
use Modules\Core\Services\PresetVersioningService;
use Modules\Core\Services\SettingsCacheCoordinator;
use Modules\Core\Support\PermissionName;

final class CMSDatabaseSeeder extends Seeder
{
    /**
     * @var Collection<string, Entity>
     */
    private Collection $entities;

    // /**
    //  * @var Collection<string, Preset>
    //  */
    // private Collection $presets;

    /**
     * @var Collection<string, Field>
     */
    private Collection $fields;

    /**
     * @return array<int, array{name: string, value: mixed, encrypted: bool, choices: ?array<int, mixed>, type: SettingTypeEnum, group_name: string, description: string}>
     */
    public static function runtimeSettingDefinitions(): array
    {
        return [
            self::setting('geocoding.cache_ttl', 604800, SettingTypeEnum::Integer, 'cms', 'Geocoding cache TTL in seconds'),
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->defaultSettings();

        Model::unguarded(function (): void {
            $this->defaultFields();
            $this->defaultEntities();
            $this->defaultRoles();
            $this->defaultContentAcls();
            $this->defaultSharedTableAcls();
        });

        // The orchestrator already flushes the settings cache once after every node succeeds
        // (SeedOrchestrator::run()); a targeted flush here keeps standalone runs of this seeder
        // (module:seed CMS, tests calling $this->seed(CMSDatabaseSeeder::class)) correct too,
        // without the blanket `cache:clear` this used to call.
        app(SettingsCacheCoordinator::class)->flushAll();
        DynamicContentsService::getInstance()->clearAllCaches();
    }

    /**
     * @return array{name: string, value: mixed, encrypted: bool, choices: ?array<int, mixed>, type: SettingTypeEnum, group_name: string, description: string}
     */
    private static function setting(string $name, mixed $value, SettingTypeEnum $type, string $group, string $description, ?array $choices = null): array
    {
        return [
            'name' => $name,
            'value' => $value,
            'encrypted' => false,
            'choices' => $choices,
            'type' => $type,
            'group_name' => $group,
            'description' => $description,
        ];
    }

    private function defaultSettings(): void
    {
        $this->logOperation(Setting::class);

        $outcome = app(SeedReconciler::class)->reconcile(
            self::internalSettingsDefinition('CMS', self::runtimeSettingDefinitions()),
        );

        $this->command?->line(
            '    - created ' . count($outcome->created) . ', realigned ' . count($outcome->realigned) . ", unchanged {$outcome->unchanged}",
        );
    }

    private function defaultFields(): void
    {
        $this->logOperation(Field::class);

        $field_model = new Field;
        $this->fields = $field_model->newQuery()->withoutGlobalScopes()->get()->keyBy('name');

        $field_model->getConnection()->transaction(function (): void {
            foreach (['subtitle'] as $field) {
                if (! $this->fields->has($field)) {
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::Text, 'options' => (object) ['max_length' => 255], 'is_translatable' => true]));
                    $this->command?->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command?->line("    - {$field} already exists");
                }
            }

            foreach (['short_content'] as $field) {
                if (! $this->fields->has($field)) {
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::Textarea, 'options' => (object) ['max_length' => 65535], 'is_translatable' => true]));
                    $this->command?->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command?->line("    - {$field} already exists");
                }
            }

            foreach (['content'] as $field) {
                if (! $this->fields->has($field)) {
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::Editor, 'options' => (object) [], 'is_translatable' => true]));
                    $this->command?->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command?->line("    - {$field} already exists");
                }
            }

            foreach (['period_from', 'period_to'] as $field) {
                if (! $this->fields->has($field)) {
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::Datetime, 'options' => (object) ['format' => 'Y-m-d H:i:s'], 'is_translatable' => false]));
                    $this->command?->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command?->line("    - {$field} already exists");
                }
            }

            $field = 'public_email';

            if (! $this->fields->has($field)) {
                $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::Email, 'options' => (object) [], 'is_translatable' => false]));
                $this->command?->line("    - {$field} <fg=green>created</>");
            } else {
                $this->command?->line("    - {$field} already exists");
            }

            $field = 'phone';

            if (! $this->fields->has($field)) {
                $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::Phone, 'options' => (object) [], 'is_translatable' => false]));
                $this->command?->line("    - {$field} <fg=green>created</>");
            } else {
                $this->command?->line("    - {$field} already exists");
            }

            foreach (['website', 'linkedin', 'twitter', 'facebook', 'instagram'] as $field) {
                if (! $this->fields->has($field)) {
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::Url, 'options' => (object) [], 'is_translatable' => false]));
                    $this->command?->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command?->line("    - {$field} already exists");
                }
            }
        });
    }

    private function defaultEntities(): void
    {
        $this->logOperation(Entity::class);

        $entity_model = new Entity;
        $this->entities = $entity_model->newQuery()->withoutGlobalScopes()->get()->keyBy('name');

        $entity_model->getConnection()->transaction(function (): void {
            $standard = 'standard';

            $entities = [
                [
                    'name' => 'post',
                    'type' => EntityType::Contents,
                    'preset' => 'standard',
                    'is_default' => true,
                    'required_fields' => ['content'],
                    'optional_fields' => ['subtitle', 'short_content'],
                ],
                [
                    'name' => 'event',
                    'type' => EntityType::Contents,
                    'preset' => 'standard',
                    'is_default' => false,
                    'required_fields' => ['content', 'period_from'],
                    'optional_fields' => ['subtitle', 'short_content', 'period_to'],
                ],
                [
                    'name' => 'contributor',
                    'type' => EntityType::Contributors,
                    'preset' => 'standard',
                    'is_default' => true,
                    'required_fields' => ['content'],
                    'optional_fields' => ['public_email', 'phone', 'website', 'linkedin', 'twitter', 'facebook', 'instagram'],
                ],
                [
                    'name' => 'category',
                    'type' => EntityType::Categories,
                    'preset' => 'standard',
                    'is_default' => true,
                    'required_fields' => ['content'],
                    'optional_fields' => ['short_content'],
                ],
            ];

            foreach ($entities as $entity) {
                if (! $this->entities->has($entity['name'])) {
                    /** @var Entity $entity */
                    $new_entity = $this->create(Entity::class, ['name' => $entity['name'], 'type' => $entity['type'], 'is_default' => $entity['is_default']]);
                    $this->entities->put($entity['name'], $new_entity);

                    /** @var Preset $preset */
                    $preset = $this->create(Preset::class, ['name' => $standard, 'entity_id' => $new_entity->id]);

                    // required fields
                    if ($entity['required_fields'] !== []) {
                        $fields = $this->fields->filter(fn (Field $field): bool => in_array($field->name, $entity['required_fields'], true));

                        foreach ($fields as $field) {
                            $this->assignFieldToPreset($preset, $field, true);
                        }
                    }

                    // optional fields
                    if ($entity['optional_fields'] !== []) {
                        $fields = $this->fields->filter(fn (Field $field): bool => in_array($field->name, $entity['optional_fields'], true));

                        foreach ($fields as $field) {
                            $this->assignFieldToPreset($preset, $field, false);
                        }
                    }

                    // Create presettable with a frozen fields snapshot after all fields are attached.
                    // Must be called after every field change since BelongsToMany bulk operations
                    // do not fire pivot model events that would otherwise trigger versioning.
                    resolve(PresetVersioningService::class)->createVersion($preset);

                    $this->command?->line("    - {$entity['name']} <fg=green>created</>");
                } else {
                    $this->command?->line("    - {$entity['name']} already exists");
                }
            }
        });
    }

    private function defaultRoles(): void
    {
        $this->logOperation(Role::class);

        /** @var class-string<Role> $role_class */
        $role_class = config('permission.models.role');

        /** @var class-string<Permission> $permission_class */
        $permission_class = config('permission.models.permission');

        $all_roles = $role_class::query()->get(['id', 'name'])->keyBy('name');

        // Permissions are named after the model table, so the CMS models must be the source:
        // categories and presets live in the shared `core_taxonomies` / `core_presets` tables,
        // which are narrowed to the CMS entities by `defaultSharedTableAcls()`.
        $cms_tables = array_map(
            static fn (string $model): string => (new $model)->getTable(),
            [Content::class, Category::class, Preset::class, Comment::class],
        );

        $name = 'publisher';

        if (! $all_roles->has($name)) {
            $this->create($role_class, [
                'name' => $name,
                'permissions' => fn () => $permission_class::whereIn('table_name', $cms_tables)
                    ->where(static fn ($query) => $query->where('name', 'like', '%.' . ActionEnum::Approve->value)
                        ->orWhere('name', 'like', '%.' . ActionEnum::Select->value))
                    ->get(),
            ]);
            $this->command?->line("    - {$name} <fg=green>created</>");
        } else {
            $this->command?->line("    - {$name} already exists");
        }

        $this->grantPublisherRelatedReads($role_class, $permission_class, $name);

        foreach (CoreDatabaseSeeder::getDefaultUserRoles() as $key => $role) {
            $role = $all_roles->get($role);

            if ($key === 'admin' && $role !== null) {
                $role->permissions()->syncWithoutDetaching(
                    $permission_class::where(static fn ($query) => $query->whereIn('table_name', $cms_tables)
                        ->orWhere('name', 'like', '%.' . ActionEnum::Select->value))
                        ->whereNot('name', 'like', '%.' . ActionEnum::Lock->value)->pluck('id'),
                );
            }
        }
    }

    /**
     * Related records obey their own permission, so the publisher reads the entities its screens load with a
     * content: the tags, locations and contributors of the content form, the pending modifications of the
     * content list. Given on every run, so an existing publisher role gains them too.
     *
     * @param  class-string<Role>  $role_class
     * @param  class-string<Permission>  $permission_class
     */
    private function grantPublisherRelatedReads(string $role_class, string $permission_class, string $name): void
    {
        $publisher = $role_class::query()->where('name', $name)->first(['id', 'guard_name']);

        if ($publisher === null) {
            return;
        }

        $names = array_map(
            static fn (string $model): string => PermissionName::forClass($model, ActionEnum::Select->value),
            [Tag::class, Location::class, Contributor::class, Modification::class],
        );

        $publisher->permissions()->syncWithoutDetaching(
            $permission_class::query()->whereIn('name', $names)->where('guard_name', $publisher->guard_name)->pluck('id'),
        );
    }

    /**
     * Seed the default row-level ACL that limits the anonymous/public reader (the
     * `guest` role) to published contents. Validity (valid_from/valid_to) is no
     * longer a global scope: this role-scoped ACL on `cms_contents.select` filters
     * the guest to the current publication window via the `@now` placeholder, while
     * staff roles (no ACL on that permission) read every content.
     */
    private function defaultContentAcls(): void
    {
        $this->logOperation(ACL::class);

        /** @var class-string<Permission> $permission_class */
        $permission_class = config('permission.models.permission');

        /** @var class-string<Role> $role_class */
        $role_class = config('permission.models.role');

        $guest = $role_class::query()->where('name', config('permission.roles.guest'))->first(['id']);

        if ($guest === null) {
            $this->command?->line('    - guest role missing, skipping content ACL');

            return;
        }

        $content = new Content;
        $permission_name = PermissionName::forModel($content, ActionEnum::Select->value);

        $permission = $permission_class::query()->where('name', $permission_name)->first(['id']);

        if ($permission === null) {
            $this->command?->line("    - permission {$permission_name} missing, skipping content ACL");

            return;
        }

        if (ACL::query()->where('permission_id', $permission->id)->where('role_id', $guest->id)->exists()) {
            $this->command?->line('    - content guest ACL already exists');

            return;
        }

        $table = $content->getTable();
        $filters = new FiltersGroup(
            filters: [
                new Filter("{$table}.valid_from", '@now', FilterOperator::LessEquals),
                new FiltersGroup(
                    filters: [
                        new Filter("{$table}.valid_to", '@now', FilterOperator::GreatEquals),
                        new Filter("{$table}.valid_to", null, FilterOperator::Equals),
                    ],
                    operator: WhereClause::Or,
                ),
            ],
            operator: WhereClause::And,
        );

        $acl = new ACL;
        $acl->setSkipValidation(true);
        $acl->forceFill([
            'permission_id' => $permission->id,
            'role_id' => $guest->id,
            'filters' => $filters,
            'description' => 'Guests (anonymous/public) read only currently-published contents.',
            'unrestricted' => false,
            'priority' => 100,
            'is_active' => true,
        ]);
        $acl->save();

        $this->command?->line('    - content guest ACL <fg=green>created</>');
    }

    /**
     * Seed the row-level ACLs that keep the CMS roles inside the CMS entities on the tables
     * every module shares (`core_taxonomies`, `core_presets`). Their permissions are not
     * CMS-specific, so granting `select` to the `publisher` role alone would expose the
     * taxonomies and presets of ERP and every other module; the ACL narrows the role to rows
     * whose entity type is a CMS one.
     */
    private function defaultSharedTableAcls(): void
    {
        $this->logOperation(ACL::class);

        /** @var class-string<Permission> $permission_class */
        $permission_class = config('permission.models.permission');

        /** @var class-string<Role> $role_class */
        $role_class = config('permission.models.role');

        $publisher = $role_class::query()->where('name', 'publisher')->first(['id']);

        if ($publisher === null) {
            $this->command?->line('    - publisher role missing, skipping shared-table ACLs');

            return;
        }

        $path = [Category::class => 'presettable.entity.type', Preset::class => 'entity.type'];

        foreach ($path as $model_class => $property) {
            $model = new $model_class;
            $permission_name = PermissionName::forModel($model, ActionEnum::Select->value);
            $permission = $permission_class::query()->where('name', $permission_name)->first(['id']);

            if ($permission === null) {
                $this->command?->line("    - permission {$permission_name} missing, skipping its ACL");

                continue;
            }

            if (ACL::query()->where('permission_id', $permission->id)->where('role_id', $publisher->id)->exists()) {
                $this->command?->line("    - {$permission_name} publisher ACL already exists");

                continue;
            }

            $acl = new ACL;
            $acl->setSkipValidation(true);
            $acl->forceFill([
                'permission_id' => $permission->id,
                'role_id' => $publisher->id,
                'filters' => new FiltersGroup(
                    filters: [new Filter($property, EntityType::values(), FilterOperator::In)],
                    operator: WhereClause::And,
                ),
                'description' => 'Publishers read only the CMS rows of a table shared with other modules.',
                'unrestricted' => false,
                'priority' => 100,
                'is_active' => true,
            ]);
            $acl->save();

            $this->command?->line("    - {$permission_name} publisher ACL <fg=green>created</>");
        }
    }

    private function assignFieldToPreset(Preset $preset, Field $field, bool $is_required): void
    {
        $pivotAttributes = ['is_required' => $is_required, 'default' => $this->getDefaultFieldValue($field, $is_required), 'preset_id' => $preset->id];
        $preset->fields()->attach($field->id, $pivotAttributes);
    }

    private function getDefaultFieldValue(Field $field, bool $is_required): mixed
    {
        return match ($field->type) {
            FieldType::Select && isset($field->options->multiple) && $field->options->multiple => [],
            FieldType::Switch => $is_required,
            FieldType::Checkbox => [],
            default => null,
        };
    }
}
