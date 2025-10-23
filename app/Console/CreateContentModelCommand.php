<?php

declare(strict_types=1);

namespace Modules\Cms\Console;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Modules\Cms\Models\Entity;
use Modules\Core\Overrides\Command;
use Override;
use Symfony\Component\Console\Input\InputArgument;

final class CreateContentModelCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'model:make-content-model {entity}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new content model <fg=blue>(✎ Modules\Cms)</fg=blue>';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $entity = $this->argument('entity');
        $class_name = Str::studly($entity);
        $file_path = module_path('Cms', 'app/Models/Contents/' . $class_name . '.php');

        if (! file_exists($file_path)) {
            if (! Entity::query()->withoutGlobalScopes()->where('name', $entity)->exists()) {
                if ($this->confirm(sprintf("Entity '%s' not found, do you want to create it?", $entity), false)) {
                    $this->call(CreateEntityCommand::class, ['entity' => $entity, '--content-model' => true]);
                }

                return;
            }

            $class_definition = file_get_contents(module_path('Cms', 'stubs/content.stub'));
            $class_definition = str_replace('$CLASS$', $class_name, $class_definition);
            file_put_contents($file_path, $class_definition);
        }
    }

    /**
     * Get the console command arguments.
     */
    #[Override]
    protected function getArguments(): array
    {
        return [
            ['entity', InputArgument::REQUIRED, 'The entity name.'],
        ];
    }
}
