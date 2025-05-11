<?php

namespace Menma977\Larapprove\Helpers;

class ApprovalHelper
{
    const APPROVE_EVENT_DRAFT = 'DRAFT';

    const APPROVE_EVENT_APPROVED = 'APPROVED';

    const APPROVE_EVENT_REJECTED = 'REJECTED';

    const APPROVE_EVENT_ROLLBACK = 'ROLLBACK';

    const APPROVAL_TYPE_PARALLEL = 'PARALLEL';

    const APPROVAL_TYPE_SEQUENTIAL = 'SEQUENTIAL';

    const CONTRIBUTOR_TYPE_AND = 'AND';

    const CONTRIBUTOR_TYPE_OR = 'OR';

    /**
     * Determine if a component can be approved based on the approval event type and levels
     *
     * @param  string  $eventType  The approval event type (PARALLEL or SEQUENTIAL)
     * @param  int  $componentLevel  The level of the component
     * @param  int  $currentEventLevel  The current level of the approval event
     */
    public static function canApproveComponent(string $eventType, int $componentLevel, int $currentEventLevel): bool
    {
        // In PARALLEL mode, any component can be approved at any time
        if ($eventType === self::APPROVAL_TYPE_PARALLEL) {
            return true;
        }

        // In SEQUENTIAL mode, only components at the current level can be approved
        return $eventType === self::APPROVAL_TYPE_SEQUENTIAL && $componentLevel === $currentEventLevel;
    }
}
