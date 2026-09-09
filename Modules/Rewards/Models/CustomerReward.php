<?php

namespace Modules\Rewards\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Context\Traits\UsesTenant;

class CustomerReward extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'customer_rewards';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'customer_email',
        'current_points',
        'lifetime_points',
        'tier',
    ];

    protected $casts = [
        'current_points' => 'integer',
        'lifetime_points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'customer_reward_id')->latest();
    }

    /**
     * Recalculate customer tier based on lifetime points.
     */
    public function updateTier(): string
    {
        $points = $this->lifetime_points;
        if ($points >= 5000) {
            $this->tier = 'Platinum';
        } elseif ($points >= 2000) {
            $this->tier = 'Gold';
        } elseif ($points >= 500) {
            $this->tier = 'Silver';
        } else {
            $this->tier = 'Bronze';
        }
        $this->save();

        return $this->tier;
    }
}
