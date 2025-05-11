<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents a model that can request approval in the system.
 *
 * This model defines which models in the application can request approvals
 * and how they are configured.
 *
 * @property string $id ULID identifier
 * @property string $name The name of the approval request type
 * @property string $model The fully qualified class name of the model that can request approval
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection<int, ApprovalFlowComponent> $components
 */
class ApprovalFlowRequest extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'model',
    ];

    /**
     * Get the flow components that use this request type.
     *
     * @return HasMany<ApprovalFlowComponent>
     */
    public function components(): HasMany
    {
        return $this->hasMany(ApprovalFlowComponent::class, 'approval_requestable_flow_id');
    }
}