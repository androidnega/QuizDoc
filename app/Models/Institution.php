<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = ['name', 'region', 'logo'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }

    /** Display name with region (e.g. "Accra Technical University - Greater Accra Region") */
    public function getDisplayNameAttribute(): string
    {
        return $this->region
            ? "{$this->name} - {$this->region}"
            : $this->name;
    }

    /** Full URL for logo (Cloudinary URL or local storage path). */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }
        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }
        return asset('storage/' . $this->logo);
    }
}
