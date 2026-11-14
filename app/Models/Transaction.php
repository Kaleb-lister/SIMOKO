<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'goal_id',
        'event_id',
        'type',
        'amount',
        'original_amount',
        'coin_value',
        'weight_delta_g',
        'source',
        'occurred_at',
        'received_at',
        'time_estimated',
        'needs_review',
        'status',
        'voided_at',
        'void_reason',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'original_amount' => 'integer',
            'coin_value' => 'integer',
            'weight_delta_g' => 'decimal:3',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'time_estimated' => 'boolean',
            'needs_review' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
