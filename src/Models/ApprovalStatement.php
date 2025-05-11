<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents an approval statement within an approval workflow.
 *
 * This model defines conditions and components for specific approval scenarios.
 *
 * @property string $id ULID identifier
 * @property int $approval_id Foreign key to the parent approval
 * @property string $name The name of this statement
 * @property bool $is_default Whether this is the default statement
 * @property Carbon $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft delete timestamp
 * @property-read Approval $approval The parent approval
 * @property-read Collection<int, ApprovalCondition> $conditions The conditions for this statement
 * @property-read Collection<int, ApprovalComponent> $components The components for this statement
 */
class ApprovalStatement extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'approval_id',
        'name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the approval that owns this statement.
     *
     * @return BelongsTo<Approval>
     */
    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }

    /**
     * Get the conditions for this statement.
     *
     * @return HasMany<ApprovalCondition>
     */
    public function conditions(): HasMany
    {
        return $this->hasMany(ApprovalCondition::class);
    }

    /**
     * Get the components for this statement.
     *
     * @return HasMany<ApprovalComponent>
     */
    public function components(): HasMany
    {
        return $this->hasMany(ApprovalComponent::class);
    }
}
