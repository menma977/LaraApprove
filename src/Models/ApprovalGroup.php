<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents a group of approval contributors.
 *
 * This model manages groups of users or entities that can approve certain workflows.
 *
 * @property string $id ULID identifier
 * @property string $name The name of the approval group
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection<int, ApprovalGroupContributor> $contributors
 */
class ApprovalGroup extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the contributors assigned to this approval group.
     *
     * @return HasMany<ApprovalGroupContributor>
     */
    public function contributors(): HasMany
    {
        return $this->hasMany(ApprovalGroupContributor::class, 'approval_group_id');
    }

}