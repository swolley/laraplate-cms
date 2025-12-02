<?php

declare(strict_types=1);

namespace Modules\Cms\Console;

use Illuminate\Console\Command;
use Modules\Cms\Jobs\TranslateModelJob;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Tag;
use Modules\Core\Helpers\LocaleContext;

final class TranslateContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cms:translate-content
                            {model? : The model to translate (content, category, tag)}
                            {--id= : Specific model ID to translate}
                            {--locale= : Specific locale to translate to}
                            {--all : Translate to all available locales}
                            {--force : Force translation even if translation exists}
                            {--sync : Run synchronously instead of queued}';

    /**
     * The console command description.
     */
    protected $description = 'Translate content, categories, or tags to other locales';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model_type = $this->argument('model') ?? 'content';
        $model_id = $this->option('id');
        $locale = $this->option('locale');
        $all_locales = $this->option('all');
        $force = $this->option('force');
        $sync = $this->option('sync');

        $locales = $all_locales ? LocaleContext::getAvailable() : ($locale ? [$locale] : []);

        $model_class = match ($model_type) {
            'content' => Content::class,
            'category' => Category::class,
            'tag' => Tag::class,
            default => null,
        };

        if (! $model_class) {
            $this->error("Invalid model type: {$model_type}. Use: content, category, or tag");

            return Command::FAILURE;
        }

        $query = $model_class::query();

        if ($model_id) {
            $query->where('id', $model_id);
        }

        $models = $query->get();
        $count = $models->count();

        if ($count === 0) {
            $this->warn('No models found to translate');

            return Command::SUCCESS;
        }

        $this->info("Found {$count} model(s) to translate");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($models as $model) {
            if ($sync) {
                // Run synchronously
                $job = new TranslateModelJob($model, $locales, $force);
                $job->handle(app(\Modules\Core\Services\Translation\TranslationService::class));
            } else {
                // Dispatch to queue
                dispatch(new TranslateModelJob($model, $locales, $force));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Translation job(s) dispatched for {$count} model(s)");

        return Command::SUCCESS;
    }
}
