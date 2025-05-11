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
 * @property string $group_id Foreign key to the parent approval group
 * @property string $contributor_id Foreign key to the contributor that belongs to this group
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Group $group The parent approval group
 * @property-read Model $contributor The user that belongs to this group
 */
class GroupContributor extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'group_id',
        'contributor_id',
    ];

    /**
     * Get the approval group that owns this contributor.
     *
     * @return BelongsTo<Group>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user that belongs to this group contributor.
     *
     * @return BelongsTo<Model>
     */
    public function contributor(): BelongsTo
    {
        return $this->belongsTo(config('lara_approve.user'), 'contributor_id');
    }
}
