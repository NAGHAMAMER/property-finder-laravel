<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Messages extends Model
{
      use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'property_id',
        'content',
        'is_read',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
