<?php

namespace TJDFT\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return config('tjdft.acl.tables.permissions');
    }
}
