<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents a contributor within an approval group.
 *
 * This model links users to approval groups, allowing them to be assigned
 * as approvers in workflows.
 *
 * @property string $id ULID identifier
 * @property string $approval_group_id Foreign key to the parent approval group
 * @property string $user_id Foreign key to the user that belongs to this group
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read ApprovalGroup $group The parent approval group
 * @property-read Model $user The user that belongs to this group
 */
class ApprovalGroupContributor extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'approval_group_id',
        'user_id',
    ];

    /**
     * Get the approval group that owns this contributor.
     *
     * @return BelongsTo<ApprovalGroup>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ApprovalGroup::class, 'approval_group_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('Lara_approve.model'), 'user_id');
    }
}