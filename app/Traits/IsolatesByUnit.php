<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait IsolatesByUnit
{
    protected static function bootIsolatesByUnit()
    {
        static::addGlobalScope('isolate_by_unit', function (Builder $builder) {
            if (Auth::check()) {
                $allowedUnits = Auth::user()->getAllowedUnitIds();

                if ($allowedUnits !== null) {
                    if (empty($allowedUnits)) {
                        $builder->whereRaw('1 = 0');
                    } else {
                        $table = $builder->getModel()->getTable();
                        $builder->whereIn($table . '.unit_id', $allowedUnits);
                    }
                }
            }
        });
    }
}