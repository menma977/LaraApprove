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
 * Represents a component instance within an approval event.
 *
 * This model tracks the status and details of individual approval components
 * as they are processed within a specific approval event/request.
 *
 * @property string $id ULID identifier
 * @property int $approval_event_id Foreign key to the parent event
 * @property int $approval_component_id Foreign key to the component definition
 * @property int $level The order/level of this component in the event
 * @property string $type The type of approval logic - 'AND' requires all conditions, 'OR' requires any condition
 * @property string $name The name of this component instance
 * @property string|null $description Description of this component's purpose
 * @property string|null $color_code The color code for UI representation
 * @property Carbon|null $approved_at Timestamp when this component was approved
 * @property Carbon|null $rejected_at Timestamp when this component was rejected
 * @property Carbon|null $canceled_at Timestamp when this component was canceled
 * @property Carbon|null $rollback_at Timestamp when this component was rolled back
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ApprovalEvent $approvalEvent The parent approval event
 * @property-read ApprovalComponent $approvalComponent The component definition
 * @property-read Collection<int, ApprovalEventContributor> $contributors The contributors for this component
 */
class ApprovalEventComponent extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'approval_event_id',
        'approval_component_id',
        'level',
        'type',
        'name',
        'description',
        'color_code',
        'approved_at',
        'rejected_at',
        'canceled_at',
        'rollback_at',
    ];

    /**
     * Get the approval event that owns this component instance.
     *
     * @return BelongsTo<ApprovalEvent>
     */
    public function approvalEvent(): BelongsTo
    {
        return $this->belongsTo(ApprovalEvent::class, 'approval_event_id', 'id')->withTrashed();
    }

    /**
     * Get the component definition for this instance.
     *
     * @return BelongsTo<ApprovalComponent>
     */
    public function approvalComponent(): BelongsTo
    {
        return $this->belongsTo(ApprovalComponent::class, 'approval_component_id', 'id')->withTrashed();
    }

    /**
     * Get the contributors assigned to approve this component instance.
     *
     * @return HasMany<ApprovalEventContributor>
     */
    public function contributors(): HasMany
    {
        return $this->hasMany(ApprovalEventContributor::class, 'approval_event_component_id', 'id');
    }
}
