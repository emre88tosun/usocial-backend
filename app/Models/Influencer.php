<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Influencer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gem_cost_per_dm',
        'bio',
    ];

    /**
     * The user associated with the influencer.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter influencers by their gem cost range.
     */
    public function scopeGemCostBetween($query, $min, $max)
    {
        return $query->whereBetween('gem_cost_per_dm', [$min, $max]);
    }
}
