<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JerseySize extends Model
{
    use HasFactory;

    protected $fillable = [
        'size_code',
        'label',
        'gender_cut',
        'chest_width_cm',
        'body_length_cm',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'sort_order' => 'integer',
        'chest_width_cm' => 'integer',
        'body_length_cm' => 'integer',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}
