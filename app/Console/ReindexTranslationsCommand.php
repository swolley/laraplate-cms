<?php

declare(strict_types=1);

namespace Modules\Cms\Console;

use Illuminate\Console\Command;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Tag;

final class ReindexTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cms:reindex-translations
                            {model? : The model to reindex (content, category, tag)}
                            {--all : Reindex all models}';

    /**
     * The console command description.
     */
    protected $description = 'Reindex search indexes with translations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model_type = $this->argument('model');
        $all = $this->option('all');

        if ($all) {
            $this->reindexModel(Content::class);
            $this->reindexModel(Category::class);
            $this->reindexModel(Tag::class);

            return Command::SUCCESS;
        }

        if (! $model_type) {
            $this->error('Please specify a model type or use --all');

            return Command::FAILURE;
        }

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

        $this->reindexModel($model_class);

        return Command::SUCCESS;
    }

    /**
     * Reindex a specific model.
     */
    private function reindexModel(string $model_class): void
    {
        $this->info("Reindexing {$model_class}...");

        $models = $model_class::query()
            ->with('translations')
            ->get();

        $count = $models->count();

        if ($count === 0) {
            $this->warn("No {$model_class} models found");

            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($models as $model) {
            $model->searchable();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Reindexed {$count} {$model_class} model(s)");
    }
}
