<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents an approval flow model that defines available approval workflows.
 *
 * This model defines the structure and available approval workflows in the system.
 *
 * @property string $id ULID identifier
 * @property string $name The name of the approval flow
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection<int, ApprovalFlowComponent> $components
 */
class ApprovalFlow extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the flow components for this flow.
     *
     * @return HasMany<ApprovalFlowComponent>
     */
    public function components(): HasMany
    {
        return $this->hasMany(ApprovalFlowComponent::class, 'approval_flow_id');
    }
}