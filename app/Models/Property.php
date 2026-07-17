<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'location',
        'price',
        'badroom',
        'bathroom',
        'area',
        'status',
        'user_id',
        'approval_status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'badroom' => 'integer',
            'bathroom' => 'integer',
            'area' => 'integer',
            'reviewed_at' => 'datetime',
            'is_favorite' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Property $property) {
            $property->images()->get()->each(function (Image $image) {
                Storage::disk('public')->delete($image->image_path);
            });

            $property->documents()->get()->each(function (PropertyDocument $document) {
                Storage::disk('local')->delete($document->file_path);
            });
        });
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeWithFavoriteState(Builder $query, ?int $userId): Builder
    {
        if (! $userId) {
            return $query;
        }

        return $query->withExists([
            'favorites as is_favorite' => fn (Builder $favoriteQuery) => $favoriteQuery->where('user_id', $userId),
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'Property_id');
    }

    public function messages()
    {
        return $this->hasMany(Messages::class, 'property_id');
    }

    public function detailed_locations()
    {
        return $this->hasOne(DetailedLocation::class, 'property_id');
    }

    public function documents()
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }
}
