<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EventType;

class Event extends Model
{
    protected $guarded = [];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }
}
