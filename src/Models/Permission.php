<?php

namespace TJDFT\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use TJDFT\Laravel\Traits\HasSearchAny;

class Permission extends Model
{
    use HasSearchAny;
    
    protected $guarded = ['id'];

    public function getTable()
    {
        return config('tjdft.acl.tables.permissions');
    }
}
