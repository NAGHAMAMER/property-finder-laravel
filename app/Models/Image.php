<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = ['Property_id', 'image_path'];

    public function Property()
    {
        return $this->belongsTo(Property::class, 'Property_id');
    }
}
