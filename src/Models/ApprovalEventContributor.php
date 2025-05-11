<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents a contributor's approval status within an approval event component.
 *
 * This model tracks individual contributor actions and statuses for specific components
 * within an approval event, including their approval, rejection, cancellation or rollback status.
 *
 * @property string $id ULID identifier for the contributor record
 * @property int $approval_event_component_id Reference to the associated approval event component
 * @property int $approval_contributor_id Reference to the contributor definition in the approval workflow
 * @property string $approvable_id Identifier of the model that has approval rights (User, Group, etc.)
 * @property string $approvable_type Full class name of the model that has approval rights
 * @property Carbon|null $approved_at Timestamp when the contributor approved the component
 * @property Carbon|null $rejected_at Timestamp when the contributor rejected the component
 * @property Carbon|null $canceled_at Timestamp when the contributor's approval was canceled
 * @property Carbon|null $rollback_at Timestamp when the contributor's approval was rolled back
 * @property Carbon $created_at Timestamp when the contributor record was created
 * @property Carbon|null $updated_at Timestamp when the contributor record was last updated
 * @property Carbon|null $deleted_at Timestamp when the contributor record was softly deleted
 * @property-read ApprovalEventComponent $eventComponent The parent approval event component relationship
 * @property-read Model $approvable The polymorphic relationship to the approving model
 */
class ApprovalEventContributor extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'approval_event_component_id',
        'approval_contributor_id',
        'approvable_id',
        'approvable_type',
        'approved_at',
        'rejected_at',
        'canceled_at',
        'rollback_at',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    /**
     * Get the event component that owns this contributor status.
     *
     * @return BelongsTo<ApprovalEventComponent>
     */
    public function eventComponent(): BelongsTo
    {
        return $this->belongsTo(ApprovalEventComponent::class, 'approval_event_component_id', 'id');
    }

    /**
     * Get the model that can approve this component.
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }
}
