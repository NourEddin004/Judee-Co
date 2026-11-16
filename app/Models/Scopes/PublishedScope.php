<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Storefront queries only ever see published products, enforced here for the whole
 * application (REQ-DB-31, REQ-STR-03). The admin panel removes it explicitly.
 */
class PublishedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('is_published'), true);
    }
}
