<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = ['user_id', 'shipment_id', 'subject', 'category', 'priority', 'status', 'body'];
}
