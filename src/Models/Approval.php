<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents an active approval workflow instance.
 *
 * This model defines an actual approval process based on a flow template.
 * It supports both parallel and sequential approval types.
 *
 * @property int $id Auto-incrementing identifier
 * @property string $name The name of this approval instance
 * @property string $flow_id Foreign key to the parent flow template
 * @property string $type The type of workflow (parallel or sequential)
 * @property Carbon $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft delete timestamp
 * @property-read Flow $flow The parent approval flow template
 * @property-read Collection<int, ApprovalStatement> $statements The approval statements
 * @property-read Collection<int, ApprovalComponent> $components The approval components through statements
 * @property-read Collection<int, ApprovalContributor> $contributors The contributors through components
 * @property-read Collection<int, ApprovalEvent> $events The approval events/requests
 */
class Approval extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'flow_id',
        'type',
    ];

    /**
     * Get the approval flow used as a template for this approval.
     *
     * @return BelongsTo<Flow>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    /**
     * Get the approval statements for this workflow.
     *
     * @return HasMany<ApprovalStatement>
     */
    public function statements(): HasMany
    {
        return $this->hasMany(ApprovalStatement::class);
    }

    /**
     * Get the components through statements for this approval workflow.
     *
     * @return HasManyThrough<ApprovalComponent>
     */
    public function components(): HasManyThrough
    {
        return $this->hasManyThrough(ApprovalComponent::class, ApprovalStatement::class);
    }

    /**
     * Get the contributors through statements and components for this approval.
     *
     * @return HasManyThrough<ApprovalContributor>
     */
    public function contributors(): HasManyThrough
    {
        return $this->hasManyThrough(ApprovalContributor::class, ApprovalComponent::class);
    }

    /**
     * Get the approval events/requests for this workflow.
     *
     * @return HasMany<ApprovalEvent>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ApprovalEvent::class);
    }
}
