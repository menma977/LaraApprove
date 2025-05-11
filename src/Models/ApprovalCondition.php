<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents a condition for an approval statement.
 *
 * This model defines specific conditions that must be met for an approval statement
 * to be considered valid. Each condition consists of a field, operator, and value
 * that are evaluated against the model being approved.
 *
 * @property string $id ULID identifier
 * @property string $approval_statement_id Foreign key to the parent approval statement
 * @property string $field The field name to evaluate
 * @property string $operator The comparison operator
 * @property string $value The value to compare against
 * @property Carbon $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft delete timestamp
 * @property-read ApprovalStatement $approvalStatement The parent approval statement
 */
class ApprovalCondition extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'approval_statement_id',
        'field',
        'operator',
        'value',
    ];

    /**
     * Get the approval statement that owns this condition.
     *
     * @return BelongsTo<ApprovalStatement>
     */
    public function approvalStatement(): BelongsTo
    {
        return $this->belongsTo(ApprovalStatement::class);
    }
}
