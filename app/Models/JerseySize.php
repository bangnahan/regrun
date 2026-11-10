<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $size_code
 * @property string $label
 * @property string|null $gender_cut
 * @property int|null $chest_width_cm
 * @property int|null $body_length_cm
 * @property bool $is_available
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Participant> $participants
 */
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

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}
