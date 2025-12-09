<?php

declare(strict_types=1);

namespace Modules\Cms\Console;

use Illuminate\Console\Command;
use Modules\Cms\Jobs\TranslateModelJob;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Tag;
use Modules\Core\Helpers\LocaleContext;

final class TranslateMissingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cms:translate-missing
                            {model? : The model to translate (content, category, tag)}
                            {--locale= : Specific locale to check for missing translations}
                            {--sync : Run synchronously instead of queued}';

    /**
     * The console command description.
     */
    protected $description = 'Find and translate models with missing translations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model_type = $this->argument('model') ?? 'content';
        $locale = $this->option('locale');
        $sync = $this->option('sync');

        $default_locale = config('app.locale');
        $locales_to_check = $locale ? [$locale] : array_filter(
            LocaleContext::getAvailable(),
            fn ($l): bool => $l !== $default_locale,
        );

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

        $this->info('Finding models with missing translations...');

        $models_to_translate = [];

        foreach ($locales_to_check as $check_locale) {
            $query = $model_class::query()
                ->whereHas('translations', function ($q) use ($default_locale): void {
                    $q->where('locale', $default_locale);
                })
                ->whereDoesntHave('translations', function ($q) use ($check_locale): void {
                    $q->where('locale', $check_locale);
                });

            $missing = $query->get();

            foreach ($missing as $model) {
                if (! isset($models_to_translate[$model->id])) {
                    $models_to_translate[$model->id] = [
                        'model' => $model,
                        'locales' => [],
                    ];
                }

                $models_to_translate[$model->id]['locales'][] = $check_locale;
            }
        }

        $count = count($models_to_translate);

        if ($count === 0) {
            $this->info('No models with missing translations found');

            return Command::SUCCESS;
        }

        $this->info("Found {$count} model(s) with missing translations");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($models_to_translate as $data) {
            $model = $data['model'];
            $locales = $data['locales'];

            if ($sync) {
                $job = new TranslateModelJob($model, $locales, false);
                $job->handle(resolve(\Modules\Core\Services\Translation\TranslationService::class));
            } else {
                dispatch(new TranslateModelJob($model, $locales, false));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Translation job(s) dispatched for {$count} model(s)");

        return Command::SUCCESS;
    }
}
