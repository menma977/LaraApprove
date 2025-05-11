<?php

namespace Menma977\Larapprove\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents an approval event that tracks the status of an approval request.
 *
 * @property string $id ULID identifier of the event
 * @property int $level The ordering level of this event
 * @property int $approval_id Foreign key to the parent approval workflow
 * @property string $requestable_id ID of the model requesting approval
 * @property string $requestable_type Class name of the model requesting approval
 * @property string $type The type of workflow (parallel or sequential)
 * @property string $status The current status of this approval
 * @property Carbon|null $approved_at Timestamp when the request was approved
 * @property Carbon|null $rejected_at Timestamp when the request was rejected
 * @property Carbon|null $canceled_at Timestamp when the request was canceled
 * @property Carbon|null $rollback_at Timestamp when the request was rolled back
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Approval $approval
 * @property-read Model $requestable
 * @property-read Collection<int, ApprovalEventComponent> $components
 * @property-read Collection<int, ApprovalEventContributor> $contributors
 */
class ApprovalEvent extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'level',
        'approval_id',
        'requestable_id',
        'requestable_type',
        'type',
        'status',
        'approved_at',
        'rejected_at',
        'canceled_at',
        'rollback_at',
    ];

    /**
     * Get the approval workflow that owns this event.
     *
     * @return BelongsTo<Approval>
     */
    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class, 'approval_id', 'id');
    }

    /**
     * Get the model that requested the approval.
     */
    public function requestable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    /**
     * Get the components of this approval event.
     *
     * @return HasMany<ApprovalEventComponent>
     */
    public function components(): HasMany
    {
        return $this->hasMany(ApprovalEventComponent::class, 'approval_event_id');
    }

    /**
     * Get the contributors through components for this approval.
     *
     * @return HasManyThrough<ApprovalContributor>
     */
    public function contributors(): HasManyThrough
    {
        return $this->hasManyThrough(ApprovalEventContributor::class, ApprovalEventComponent::class);
    }
}
