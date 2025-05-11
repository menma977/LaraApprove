<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Represents an approver within a workflow component.
 *
 * This model defines individual approvers and their conditions within a workflow component,
 * supporting both 'AND' and 'OR' approval types. It manages the relationship between
 * approval components and the entities that can approve them.
 *
 * @property string $id ULID identifier of the approver
 * @property int $approval_component_id Foreign key to the parent component
 * @property string $approvable_type The model type that can approve
 * @property string $approvable_id The model ID that can approve
 * @property array|null $conditions Additional conditions for approval
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ApprovalComponent $approvalComponent The parent approval component
 * @property-read Model $approvable The model that can approve
 * @property-read Collection<int, ApprovalEventContributor> $eventContributors The event approvers associated with this contributor
 */
class ApprovalContributor extends Model
{
    use HasUlids, SoftDeletes;

    protected $with = ['approvable'];

    protected $fillable = [
        'approval_component_id',
        'approvable_id',
        'approvable_type',
        'conditions',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    /**
     * Get the approval component that owns this approver.
     *
     * @return BelongsTo<ApprovalComponent>
     */
    public function approvalComponent(): BelongsTo
    {
        return $this->belongsTo(ApprovalComponent::class, 'approval_component_id', 'id')->withTrashed();
    }

    /**
     * Get the model that can approve.
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the event approvers associated with this contributor.
     *
     * @return HasMany<ApprovalEventContributor>
     */
    public function eventContributors(): HasMany
    {
        return $this->hasMany(ApprovalEventContributor::class, 'approval_contributor_id');
    }
}
