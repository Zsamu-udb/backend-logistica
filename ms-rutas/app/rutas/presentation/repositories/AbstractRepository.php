<?php
declare(strict_types=1);

namespace App\rutas\Presentation\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class AbstractRepository
{
    protected Model $model;

    public function getAll(): Collection
    {
        return $this->model->newQuery()->get();
    }

    public function getById(int $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data): ?Model
    {
        $record = $this->getById($id);
        if ($record) {
            $record->fill($data);
            $record->save();
        }
        return $record;
    }

    public function delete(int $id): bool
    {
        $record = $this->getById($id);
        if ($record) {
            return (bool) $record->delete();
        }
        return false;
    }
}