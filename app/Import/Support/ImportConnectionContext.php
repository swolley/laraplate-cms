<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class ImportConnectionContext
{
    private readonly string $connection_name;

    public function __construct(
        private readonly Model $root_model,
    ) {
        $this->connection_name = $root_model->getConnection()->getName();
    }

    public function rootModel(): Model
    {
        return $this->root_model;
    }

    public function connection(): ConnectionInterface
    {
        return $this->root_model->getConnection();
    }

    public function connectionName(): string
    {
        return $this->connection_name;
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model_class
     * @return TModel
     */
    public function model(string $model_class): Model
    {
        /** @var TModel $model */
        $model = new $model_class;

        $this->assertModel($model);

        return $model;
    }

    /**
     * @param  list<class-string<Model>>  $model_classes
     */
    public function preflight(array $model_classes): void
    {
        foreach (array_unique($model_classes) as $model_class) {
            $this->model($model_class);
        }
    }

    public function assertModel(Model $model): void
    {
        $participant_name = $model->getConnection()->getName();

        if ($this->connection_name !== $participant_name) {
            throw new LogicException(sprintf(
                'Import model [%s] resolves connection [%s], expected root [%s] connection [%s].',
                $model::class,
                $participant_name,
                $this->root_model::class,
                $this->connection_name,
            ));
        }
    }
}
