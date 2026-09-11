<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InAppNotification extends Model
{
    use HasUuids;

    protected $table = 'notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'type', 'channel', 'title', 'body', 'data', 'read_at'];

    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];
}
