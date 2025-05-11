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
 * @property string $name The name of this component
 * @property string $approval_flow_id Foreign key to the parent flow
 * @property string $approval_requestable_flow_id Foreign key to the requestable model type
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read ApprovalFlow $flow The parent approval flow
 * @property-read ApprovalFlowRequest $requestFlow The requestable model configuration
 */
class ApprovalFlowComponent extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'approval_flow_id',
        'name',
        'approval_request_id',
    ];

    /**
     * Get the approval flow that owns this component.
     *
     * @return BelongsTo<ApprovalFlow>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    /**
     * Get the approval request type for this component.
     *
     * @return BelongsTo<ApprovalFlowRequest>
     */
    public function requestFlow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlowRequest::class, 'approval_request_id');
    }
}