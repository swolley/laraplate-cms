<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Seeders;

use stdClass;
use Modules\Cms\Models\Field;
use Modules\Core\Models\Role;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Preset;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Casts\EntityType;
use Modules\Core\Casts\ActionEnum;
use Modules\Core\Overrides\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;

final class CmsDatabaseSeeder extends Seeder
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
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguarded(function (): void {
            $this->defaultFields();
            $this->defaultEntities();
            $this->defaultRoles();
        });
    }

    private function defaultFields(): void
    {
        $this->logOperation(Field::class);

        $this->fields = Field::query()->withoutGlobalScopes()->get()->keyBy('name');

        $this->db->transaction(function (): void {
            foreach (['kicker', 'title', 'subtitle'] as $field) {
                if (! $this->fields->has($field)) {
                    $options = (object) ['max_length' => 255];
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::TEXT, 'options' => $options]));
                    $this->command->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command->line("    - {$field} already exists");
                }
            }

            foreach (['short_content'] as $field) {
                if (! $this->fields->has($field)) {
                    $options = (object) ['max_length' => 65535];
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::TEXTAREA, 'options' => $options]));
                    $this->command->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command->line("    - {$field} already exists");
                }
            }

            foreach (['content'] as $field) {
                if (! $this->fields->has($field)) {
                    $options = new stdClass();
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::JSON, 'options' => $options]));
                    $this->command->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command->line("    - {$field} already exists");
                }
            }

            foreach (['period_from', 'period_to'] as $field) {
                if (! $this->fields->has($field)) {
                    $options = (object) ['format' => 'Y-m-d H:i:s'];
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::DATETIME, 'options' => $options]));
                    $this->command->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command->line("    - {$field} already exists");
                }
            }

            $field = 'public_email';

            if (! $this->fields->has($field)) {
                $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::EMAIL, 'options' => (object) []]));
                $this->command->line("    - {$field} <fg=green>created</>");
            } else {
                $this->command->line("    - {$field} already exists");
            }

            $field = 'phone';

            if (! $this->fields->has($field)) {
                $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::PHONE, 'options' => (object) []]));
                $this->command->line("    - {$field} <fg=green>created</>");
            } else {
                $this->command->line("    - {$field} already exists");
            }

            foreach (['website', 'linkedin', 'twitter', 'facebook', 'instagram'] as $field) {
                if (! $this->fields->has($field)) {
                    $this->fields->put($field, $this->create(Field::class, ['name' => $field, 'type' => FieldType::URL, 'options' => (object) []]));
                    $this->command->line("    - {$field} <fg=green>created</>");
                } else {
                    $this->command->line("    - {$field} already exists");
                }
            }
        });
    }

    private function defaultEntities(): void
    {
        $this->logOperation(Entity::class);

        $this->entities = Entity::query()->withoutGlobalScopes()->get()->keyBy('name');

        $this->db->transaction(function (): void {
            $standard = 'standard';

            $entities = [
                [
                    'name' => 'article',
                    'type' => EntityType::CONTENTS,
                    'preset' => 'standard',
                    'required_fields' => ['title', 'content'],
                    'optional_fields' => ['kicker', 'subtitle', 'short_content'],
                ],
                [
                    'name' => 'multimedia',
                    'type' => EntityType::CONTENTS,
                    'preset' => 'standard',
                    'required_fields' => ['title', 'content'],
                    'optional_fields' => ['subtitle', 'short_content'],
                ],
                [
                    'name' => 'event',
                    'type' => EntityType::CONTENTS,
                    'preset' => 'standard',
                    'required_fields' => ['title', 'content', 'period_from'],
                    'optional_fields' => ['subtitle', 'short_content', 'period_to'],
                ],
                [
                    'name' => 'survey',
                    'type' => EntityType::CONTENTS,
                    'preset' => 'standard',
                    'required_fields' => ['title', 'content'],
                    'optional_fields' => ['subtitle', 'short_content'],
                ],
                [
                    'name' => 'author',
                    'type' => EntityType::AUTHORS,
                    'preset' => 'standard',
                    'required_fields' => [],
                    'optional_fields' => ['public_email', 'phone', 'website', 'content', 'linkedin', 'twitter', 'facebook', 'instagram'],
                ],
            ];

            foreach ($entities as $entity) {
                if (! $this->entities->has($entity['name'])) {
                    /** @var Entity $entity */
                    $new_entity = $this->create(Entity::class, ['name' => $entity['name'], 'type' => $entity['type']]);
                    $this->entities->put($entity['name'], $new_entity);

                    /** @var Preset $preset */
                    $preset = $this->create(Preset::class, ['name' => $standard, 'entity_id' => $new_entity->id]);

                    // required fields
                    if ($entity['required_fields'] !== []) {
                        $fields = $this->fields->filter(fn (Field $field) => in_array($field->name, $entity['required_fields'], true));

                        foreach ($fields as $field) {
                            $this->assignFieldToPreset($preset, $field, true);
                        }
                    }

                    // optional fields
                    if ($entity['optional_fields'] !== []) {
                        $fields = $this->fields->filter(fn (Field $field) => in_array($field->name, $entity['optional_fields'], true));

                        foreach ($fields as $field) {
                            $this->assignFieldToPreset($preset, $field, false);
                        }
                    }

                    $this->command->line("    - {$entity['name']} <fg=green>created</>");
                } else {
                    $this->command->line("    - {$entity['name']} already exists");
                }
            }
        });
    }

    private function defaultRoles(): void
    {
        $this->logOperation(Role::class);

        $role_class = config('permission.models.role');
        $permission_class = config('permission.models.permission');

        $name = 'publisher';

        if (! Role::whereName($name)->exists()) {
            $role = $this->create($role_class, ['name' => $name]);
            $role->givePermissionTo(
                $permission_class::whereIn('table_name', ['contents', 'categories', 'presets'])
                    ->where(function ($query): void {
                        $query->where('name', 'like', '%.' . ActionEnum::APPROVE->value)
                            ->orWhere('name', 'like', '%.' . ActionEnum::SELECT->value);
                    })->get(),
            );
            $this->command->line("    - {$name} <fg=green>created</>");
        } else {
            $this->command->line("    - {$name} already exists");
        }

        foreach (CoreDatabaseSeeder::getDefaultUserRoles() as $key => $role) {
            $role = $role_class::whereName($role['name'])->first(['id']);

            if ($key === 'admin') {
                $role->givePermissionTo(
                    $permission_class::where(function ($query): void {
                        $query->whereIn('table_name', ['contents', 'categories', 'presets'])
                            ->orWhere('name', 'like', '%.' . ActionEnum::SELECT->value);
                    })->whereNot('name', 'like', '%.' . ActionEnum::LOCK->value)->get(),
                );
            }
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
            FieldType::SELECT && isset($field->options->multiple) && $field->options->multiple => [],
            FieldType::SWITCH => $is_required,
            FieldType::CHECKBOX => [],
            default => null,
        };
    }
}
