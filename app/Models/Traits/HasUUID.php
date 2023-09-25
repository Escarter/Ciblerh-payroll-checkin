<?php

namespace App\Models\Traits;

use App\Models\Absence;
use App\Models\AdvanceSalary;
use Ramsey\Uuid\Uuid;

trait HasUUID
{
    protected static function bootHasUUID()
    {
        // parent::boot();

        static::creating(function ($model) {
            $uuidField = $model->getUUIDField();
            if (empty($model->$uuidField)) {

                $uuid = static::generateUUID();
                while ($model->where($uuidField, $uuid)->first()) {
                    $uuid = static::generateUUID();
                }

                $model->$uuidField = $uuid;
            }
        });
    }
    public function getUUIDField()
    {
        return !empty($this->uuidField) ? $this->uuidField : 'uuid';
    }

    public function scopeByUUID($query, $uuid)
    {
        return $query->where($this->getUUIDField(), $uuid);
    }

    public static function findByUuid(string $uuid)
    {
        return static::byUUID($uuid)->first();
    }

    protected static function generateUUID()
    {
        return (string) Uuid::uuid4();
    }
}
