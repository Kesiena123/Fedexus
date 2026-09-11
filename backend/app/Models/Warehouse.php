<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'city', 'country', 'address'];

    protected $casts = ['address' => 'array'];
}
