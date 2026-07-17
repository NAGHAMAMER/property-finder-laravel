<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    protected $hidden = [
        'file_path',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
