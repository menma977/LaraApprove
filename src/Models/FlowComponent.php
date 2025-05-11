<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents a component within an approval flow.
 *
 * This model defines how approval flows are linked to requestable models,
 * creating a reusable approval workflow.
 *
 * @property string $id ULID identifier
 * @property string $flow_id Foreign key to the parent flow
 * @property string $name The name of this component
 * @property string $model The fully qualified model class name
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Flow $flow The parent approval flow
 */
class FlowComponent extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'flow_id',
        'name',
        'model',
    ];

    /**
     * Get the approval flow that owns this component.
     *
     * @return BelongsTo<Flow>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }
}
