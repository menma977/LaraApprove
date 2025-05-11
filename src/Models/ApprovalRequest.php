<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents an approval request model that defines available approval workflows.
 *
 * This model defines the structure and available approval requests in the system.
 *
 * @property string $id ULID identifier
 * @property string $name The name of the approval request
 * @property string $model The model class this approval request is for
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Flow> $flows
 */
class ApprovalRequest extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'model',
    ];

    /**
     * Get the flows for this approval request.
     *
     * @return HasMany<Flow>
     */
    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class, 'approval_request_id');
    }
}
