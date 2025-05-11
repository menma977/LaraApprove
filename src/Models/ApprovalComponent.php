<?php

namespace Menma977\Larapprove\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents a component within an approval workflow statement.
 *
 * This model defines individual steps or stages in an approval workflow,
 * including their properties and relationships to approvers.
 *
 * @property string $id ULID identifier of the component
 * @property int $approval_statement_id Foreign key to the parent workflow statement
 * @property int $level The order/level of this component in the workflow
 * @property string $type The type of approval logic ('and'/'or')
 * @property string $name The name of this component
 * @property string|null $description Description of this component's purpose
 * @property string|null $color_code The color code for UI representation
 * @property bool $can_drag Whether this component can be reordered
 * @property bool $can_edit Whether this component can be edited
 * @property bool $can_delete Whether this component can be deleted
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Approval $statement The parent workflow statement
 * @property-read Collection<int, ApprovalContributor> $contributors The approvers for this component
 * @property-read Collection<int, ApprovalEventComponent> $eventComponents The event components for this component
 */
class ApprovalComponent extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'approval_statement_id',
        'level',
        'type',
        'name',
        'description',
        'color_code',
        'can_drag',
        'can_edit',
        'can_delete',
    ];

    protected $casts = [
        'can_drag' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
    ];

    /**
     * Get the workflow statement that owns this component.
     *
     * @return BelongsTo<Approval>
     */
    public function statement(): BelongsTo
    {
        return $this->belongsTo(Approval::class, 'approval_statement_id', 'id')->withTrashed();
    }

    /**
     * Get the approvers assigned to this workflow component.
     *
     * @return HasMany<ApprovalContributor>
     */
    public function contributors(): HasMany
    {
        return $this->hasMany(ApprovalContributor::class, 'approval_component_id');
    }

    /**
     * Get the event components for this component.
     *
     * @return HasMany<ApprovalEventComponent>
     */
    public function eventComponents(): HasMany
    {
        return $this->hasMany(ApprovalEventComponent::class, 'approval_component_id');
    }
}
