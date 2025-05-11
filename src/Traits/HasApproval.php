<?php

namespace Menma977\Larapprove\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Menma977\Larapprove\Models\ApprovalEvent;
use Menma977\Larapprove\Services\ApprovalService;

trait HasApproval
{
    /**
     * Defines a polymorphic relationship.
     *
     * This method allows the model to behave as a polymorphic relation target,
     * enabling other models to associate with it through a 'morphTo' relationship.
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function approval(): MorphTo
    {
        return $this->morphTo(ApprovalEvent::class);
    }

    public function getCanApproveAttribute(): bool
    {
        if (! $this instanceof Model) {
            return false;
        }

        return ApprovalService::model($this)->canApprove() ?? false;
    }
}
