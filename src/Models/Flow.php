<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents an approval flow model that defines reusable approval workflows.
 *
 * This model serves as the foundation for creating and managing approval workflows in the system.
 * Each flow can have multiple components that define the specific models requiring approval.
 *
 * @property string $id ULID identifier
 * @property string $name The name of the approval flow
 * @property Carbon $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft delete timestamp
 * @property-read Collection<int, FlowComponent> $components The flow components defining approvable models
 */
class Flow extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the flow components for this flow.
     *
     * @return HasMany<FlowComponent>
     */
    public function components(): HasMany
    {
        return $this->hasMany(FlowComponent::class);
    }
}
