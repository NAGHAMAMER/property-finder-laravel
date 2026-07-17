<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Search extends Model
{
      use HasFactory;
     protected $fillable = [
      'user_id',  'type', 'location',  'price_max',
        'price_min',
        'area_max',
        'area_min',
    ];
      public function user() {
    return $this->belongsTo(User::class);
}
}
