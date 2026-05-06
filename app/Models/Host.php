<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Host extends Model
{
    protected $fillable = [
        'nickname',
        'first_name',
        'last_name',
    ];
}
