<?php

namespace Menma977\Larapprove\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Menma977\Larapprove\Helpers\ApprovalHelper;
use Menma977\Larapprove\Models\Approval;
use Menma977\Larapprove\Models\ApprovalComponent;
use Menma977\Larapprove\Models\ApprovalCondition;
use Menma977\Larapprove\Models\ApprovalEvent;
use Menma977\Larapprove\Models\ApprovalEventComponent;
use Menma977\Larapprove\Models\ApprovalEventContributor;
use Menma977\Larapprove\Models\ApprovalStatement;
use RuntimeException;

/**
 * Service class for handling approval workflow operations.
 * Manages the creation, submission, rejection and rollback of approval requests.
 */
class ApprovalService
{
    /** @var string The model class name requesting approval */
    protected string $module_type;

    /** @var int|string The ID of the model requesting approval */
    protected int|string $module_id;

    /** @var int The ID of the user performing the approval action */
    protected int $user_id;

    /** @var string The type of approval event */
    protected string $type;

    /** @var string The status of the approval event */
    protected string $status;

    /** @var Model|null Instance of the model requesting approval */
    protected ?Model $modelInstance = null;

    /**
     * Initialize the approval service with a model instance.
     *
     * @param  Model  $morphModel  The model instance requesting approval
     *
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public static function model(Model $morphModel): static
    {
        $instance = new static;
        $instance->module_type = $morphModel->getMorphClass();
        $instance->module_id = $morphModel->id;
        $instance->modelInstance = $morphModel;

        return $instance;
    }

    /**
     * Set the user ID for the approval action.
     *
     * @param  int  $userId  The ID of the user performing the approval action
     */
    public function user(int $userId): static
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Set the type of the approval event.
     *
     * @param  string  $type  The type of approval event
     */
    public function type(string $type = ApprovalHelper::APPROVE_EVENT_DRAFT): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the status of the approval event.
     *
     * @param  string  $status  The status to set
     */
    public function status(string $status = ApprovalHelper::APPROVE_EVENT_DRAFT): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Check if the current user can approve the current approval event component.
     *
     * Validates weather:
     * - The event is not already processed
     * - The user is a valid contributor
     * - The component can be approved based on the workflow type
     *
     * @return bool True if the user can approve, false otherwise
     */
    public function canApprove(): bool
    {
        $approvalEvent = $this->ensureModelExists();
        $authId = $this->user_id;

        if (! $authId) {
            return false;
        }

        // Completed events cannot be approved again
        if ($approvalEvent->approved_at !== null || $approvalEvent->rejected_at !== null || $approvalEvent->canceled_at !== null) {
            return false;
        }

        // Get components based on an approval type
        $componentsQuery = $approvalEvent->components();

        // For SEQUENTIAL type, only check components at the current level
        if ($approvalEvent->type === ApprovalHelper::APPROVAL_TYPE_SEQUENTIAL) {
            $componentsQuery->where('level', $approvalEvent->level);
        }
        // For PARALLEL type, check all components

        $components = $componentsQuery->get();

        // If there are no components that need approval
        if ($components->isEmpty()) {
            return false;
        }

        foreach ($components as $component) {
            // Skip components that have already been processed
            if ($component->approved_at !== null || $component->rejected_at !== null ||
                $component->canceled_at !== null || $component->rollback_at !== null) {
                continue;
            }

            // Check if the component can be approved based on the event type and level
            if (! ApprovalHelper::canApproveComponent(
                $approvalEvent->type,
                $component->level,
                $approvalEvent->level
            )) {
                continue;
            }

            // Check if the current user is a contributor for this component
            $canApproveComponent = false;
            if ($component->contributors->isEmpty()) {
                return true; // If there are no contributors, anyone can approve
            }

            foreach ($component->contributors as $contributor) {
                // Skip if the contributor is not the current user
                if ($contributor->approvable_id !== $authId) {
                    continue;
                }

                // Contributors who have already taken action cannot approve again
                if ($contributor->approved_at !== null || $contributor->rejected_at !== null ||
                    $contributor->canceled_at !== null || $contributor->rollback_at !== null) {
                    continue;
                }

                // For OR type, any single contributor is enough
                $canApproveComponent = true;
                if ($component->type === ApprovalHelper::CONTRIBUTOR_TYPE_OR) {
                    break;
                } // For AND type, this user is one of those who must approve
            }

            // If the user can approve any component, return true
            if ($canApproveComponent) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create or retrieve a draft approval event.
     *
     * @return ApprovalEvent The draft approval event
     */
    public function draft(): ApprovalEvent
    {
        return $this->init();
    }

    /**
     * Submit an approval for the current component.
     * Updates the approval status based on the workflow type and conditions.
     *
     * @return ApprovalEvent The updated approval event
     */
    public function submit(): ApprovalEvent
    {
        $approvalEvent = $this->init();

        $approvalEventComponent = ApprovalEventComponent::where('approval_event_id', $approvalEvent->id)->where('level', $approvalEvent->level)->first();
        if (! $approvalEventComponent) {
            $approvalEvent->status = ApprovalHelper::APPROVE_EVENT_APPROVED;
            $approvalEvent->approved_at = now();
            $approvalEvent->save();
        }

        $approvalEventContributor = ApprovalEventContributor::where('approval_event_component_id', $approvalEventComponent->id)->where('approvable_id', $this->user_id)->get();

        $authId = $this->user_id;
        if ($approvalEventComponent->type === ApprovalHelper::CONTRIBUTOR_TYPE_OR) {
            if ($approvalEventContributor->where('approvable_id', $authId)->count()) {
                // Using a query builder to update all matching contributors
                ApprovalEventContributor::where('approval_event_component_id', $approvalEventComponent->id)
                    ->where('approvable_id', $authId)
                    ->update(['approved_at' => now()]);
            }
        } else {
            $contributor = $approvalEventContributor->where('approvable_id', $authId)->first();

            if ($contributor) {
                $contributor->approved_at = now();
                $contributor->save();
            }
        }

        $totalContributor = $approvalEventContributor->count();
        $approvalEventContributor = ApprovalEventContributor::where('approval_event_component_id', $approvalEventComponent->id)->get();

        if ($approvalEventComponent->type === ApprovalHelper::CONTRIBUTOR_TYPE_OR) {
            if ($approvalEventContributor->whereNotNull('approved_at')->count() > 0) {
                $approvalEventComponent->approved_at = now();
            }

            // OR type: If any contributor rejects, the component is rejected
            if ($approvalEventContributor->whereNotNull('rejected_at')->count() > 0) {
                $approvalEventComponent->rejected_at = now();
            }

            // OR type: If any contributor cancels, the component is canceled
            if ($approvalEventContributor->whereNotNull('canceled_at')->count() > 0) {
                $approvalEventComponent->canceled_at = now();
            }

            // OR type: If any contributor performs rollback, the component is rolled back
            if ($approvalEventContributor->whereNotNull('rollback_at')->count() > 0) {
                $approvalEventComponent->rollback_at = now();
            }
        } elseif ($approvalEventComponent->type === ApprovalHelper::CONTRIBUTOR_TYPE_AND) {
            // Check if all contributors have approved
            $approvedCount = $approvalEventContributor->whereNotNull('approved_at')->count();
            if ($approvedCount === $totalContributor) {
                $approvalEventComponent->approved_at = now();
            }

            // If any contributor rejects, the component is rejected and the process cancels
            if ($approvalEventContributor->whereNotNull('rejected_at')->count() > 0) {
                $approvalEventComponent->rejected_at = now();

                // Cancel other contributors' approval processes
                foreach ($approvalEventContributor->whereNull('rejected_at') as $contributor) {
                    $contributor->canceled_at = now();
                    $contributor->save();
                }
            }

            // If any contributor cancels, the component is canceled
            if ($approvalEventContributor->whereNotNull('canceled_at')->count() > 0) {
                $approvalEventComponent->canceled_at = now();
            }

            // If any contributor performs rollback, the component is rolled back
            if ($approvalEventContributor->whereNotNull('rollback_at')->count() > 0) {
                $approvalEventComponent->rollback_at = now();
            }
        }

        $approvalEventComponent->save();

        // Get all components for this approval event to check overall completion status
        $allComponents = ApprovalEventComponent::where('approval_event_id', $approvalEvent->id)->get();

        $unprocessedComponents = $allComponents->filter(fn ($component) => $component->approved_at === null && $component->rejected_at === null && $component->canceled_at === null && $component->rollback_at === null);

        // If all components have been processed (none are unprocessed), update the approval event status
        if ($unprocessedComponents->isEmpty()) {
            $currentLevelComponents = $allComponents->where('level', $approvalEvent->level);
            $highestLevel = $allComponents->max('level');

            // Check if we're at the highest level and all components at this level have been processed
            if ($approvalEvent->level === (int) $highestLevel) {
                // Check if any component was rejected - one rejection means the whole process is rejected
                if ($allComponents->whereNotNull('rejected_at')->isNotEmpty()) {
                    $approvalEvent->status = ApprovalHelper::APPROVE_EVENT_REJECTED;
                    $approvalEvent->rejected_at = now();
                } elseif ($allComponents->whereNotNull('canceled_at')->isNotEmpty()) {
                    $approvalEvent->status = ApprovalHelper::APPROVE_EVENT_ROLLBACK;
                    $approvalEvent->canceled_at = now();
                } elseif ($allComponents->whereNotNull('rollback_at')->isNotEmpty()) {
                    $approvalEvent->status = ApprovalHelper::APPROVE_EVENT_ROLLBACK;
                    $approvalEvent->rollback_at = now();
                } else {
                    $approvalEvent->status = ApprovalHelper::APPROVE_EVENT_APPROVED;
                    $approvalEvent->approved_at = now();
                }

                $approvalEvent->save();
            } else {
                // If all components at the current level are approved, move to the next level
                if ($currentLevelComponents->whereNull('approved_at')->isEmpty()) {
                    $approvalEvent->level += 1;
                    $approvalEvent->save();
                }
            }
        }

        return $approvalEvent;
    }

    /**
     * Reject the current approval component.
     * Marks the component, event and related items as rejected.
     *
     * @return ApprovalEvent The rejected approval event
     *
     * @throws InvalidArgumentException If the event cannot be rejected
     */
    public function reject(): ApprovalEvent
    {
        $approvalEvent = $this->ensureModelExists();

        // Check if the event can be rejected
        if ($approvalEvent->approved_at !== null || $approvalEvent->rejected_at !== null || $approvalEvent->canceled_at !== null) {
            throw new InvalidArgumentException("Approval event for model '$this->module_type' with ID '$this->module_id' cannot be rejected because it's already processed.");
        }

        $approvalEventComponent = ApprovalEventComponent::where('approval_event_id', $approvalEvent->id)->where('level', $approvalEvent->level)->first();

        if (! $approvalEventComponent) {
            throw new InvalidArgumentException("No active component found for approval event at level $approvalEvent->level");
        }

        // Mark contributor as rejected
        $contributor = ApprovalEventContributor::where('approval_event_component_id', $approvalEventComponent->id)->where('approvable_id', $this->user_id)->first();

        if ($contributor) {
            $contributor->rejected_at = now();
            $contributor->save();

            // Update component status
            $approvalEventComponent->rejected_at = now();
            $approvalEventComponent->save();

            // Mark the entire approval event as rejected
            $approvalEvent->status = ApprovalHelper::APPROVE_EVENT_REJECTED;
            $approvalEvent->rejected_at = now();
            $approvalEvent->save();

            // Cancel any pending contributors
            ApprovalEventContributor::where('approval_event_component_id', $approvalEventComponent->id)
                ->whereNull('approved_at')
                ->whereNull('rejected_at')
                ->whereNull('canceled_at')
                ->whereNull('rollback_at')
                ->update(['canceled_at' => now()]);

            // Cancel any other pending components
            ApprovalEventComponent::where('approval_event_id', $approvalEvent->id)
                ->whereNull('approved_at')
                ->whereNull('rejected_at')
                ->whereNull('canceled_at')
                ->whereNull('rollback_at')
                ->update(['canceled_at' => now()]);
        } else {
            throw new InvalidArgumentException("User with ID $this->user_id is not a contributor for this approval component.");
        }

        return $approvalEvent;
    }

    /**
     * Rollback an approval event to draft status.
     * Resets all components and contributors to the initial state.
     *
     * @return ApprovalEvent The rolled back approval event
     *
     * @throws InvalidArgumentException If the event cannot be rolled back
     */
    public function rollback(): ApprovalEvent
    {
        $approvalEvent = $this->ensureModelExists();

        // If approval is already approved, rejected, or canceled, it can't be rolled back
        if ($approvalEvent->approved_at !== null) {
            throw new InvalidArgumentException("Approval event for model '$this->module_type' with ID '$this->module_id' cannot be rolled back because it's already approved.");
        }

        $approvalEventComponent = ApprovalEventComponent::where('approval_event_id', $approvalEvent->id)
            ->where('level', $approvalEvent->level)
            ->first();

        if (! $approvalEventComponent) {
            throw new InvalidArgumentException("No active component found for approval event at level $approvalEvent->level");
        }

        // Mark contributor as rolled back
        $contributor = ApprovalEventContributor::where('approval_event_component_id', $approvalEventComponent->id)
            ->where('approvable_id', $this->user_id)
            ->first();

        if ($contributor) {
            $contributor->rollback_at = now();
            $contributor->save();

            // Update component status
            $approvalEventComponent->rollback_at = now();
            $approvalEventComponent->save();

            // Reset the approval event to draft status
            $approvalEvent->status = ApprovalHelper::APPROVE_EVENT_DRAFT;
            $approvalEvent->level = 0; // Reset to the first level
            $approvalEvent->approved_at = null;
            $approvalEvent->rejected_at = null;
            $approvalEvent->canceled_at = null;
            $approvalEvent->rollback_at = now();
            $approvalEvent->save();

            // Reset all components to draft status
            ApprovalEventComponent::where('approval_event_id', $approvalEvent->id)->update([
                'approved_at' => null,
                'rejected_at' => null,
                'canceled_at' => null,
                'rollback_at' => null,
            ]);

            // Reset all contributors to draft status
            ApprovalEventContributor::whereHas('eventComponent', fn ($query) => $query->where('approval_event_id', $approvalEvent->id))->update([
                'approved_at' => null,
                'rejected_at' => null,
                'canceled_at' => null,
                'rollback_at' => null,
            ]);

            // Mark only the current contributor's rollback
            $contributor->rollback_at = now();
            $contributor->save();
        } else {
            throw new InvalidArgumentException("User with ID $this->user_id is not a contributor for this approval component.");
        }

        return $approvalEvent;
    }

    /**
     * Initialize or retrieve an approval event.
     * Creates components and contributors based on the approval configuration.
     *
     * @return ApprovalEvent The initialized approval event
     *
     * @throws RuntimeException If no applicable approval statement is found
     */
    private function init(): ApprovalEvent
    {
        $approval = $this->ensureMethodExists();

        $approvalEvent = ApprovalEvent::where('requestable_type', $this->module_type)->where('requestable_id', $this->module_id)->first();
        if (! $approvalEvent) {
            $approvalEvent = new ApprovalEvent;
            $approvalEvent->approval_id = $approval->id;
            $approvalEvent->requestable_type = $this->module_type;
            $approvalEvent->requestable_id = $this->module_id;
            $approvalEvent->level = 0;
            $approvalEvent->status = $this->status;
            $approvalEvent->save();

            $statements = ApprovalStatement::where('approval_id', $approval->id)->get();
            $matchedStatementId = null;
            $defaultStatementId = null;
            foreach ($statements as $statement) {
                if ($statement->is_default) {
                    $defaultStatementId = $statement->id;
                }

                $allConditionsMet = true;
                if ($statement->conditions->isEmpty()) {
                    if (! $statement->is_default) {
                        $allConditionsMet = false;
                    } else {
                        continue;
                    }

                } else {
                    foreach ($statement->conditions as $condition) {
                        if (! $this->evaluateCondition($condition)) {
                            $allConditionsMet = false;
                            break;
                        }
                    }
                }

                if ($allConditionsMet && ! $statement->is_default) {
                    $matchedStatementId = $statement->id;
                    break;
                }
            }
            $statementId = $matchedStatementId ?? $defaultStatementId;
            if ($statementId === null) {
                throw new RuntimeException("No applicable approval statement found for model '$this->module_type' and no default statement configured for approval ID '$approval->id'.");
            }

            $approvalComponents = ApprovalComponent::where('approval_statement_id', $statementId)->get();
            foreach ($approvalComponents as $approvalComponent) {
                $approvalEventComponent = new ApprovalEventComponent;
                $approvalEventComponent->approval_event_id = $approvalEvent->id;
                $approvalEventComponent->approval_component_id = $approvalComponent->id;
                $approvalEventComponent->level = $approvalComponent->level;
                $approvalEventComponent->name = $approvalComponent->name;
                $approvalEventComponent->description = $approvalComponent->description;
                $approvalEventComponent->color_code = $approvalComponent->color_code;
                $approvalEventComponent->type = $approvalComponent->type;
                $approvalEventComponent->save();

                foreach ($approvalComponent->contributors as $contributor) {
                    $approvalEventContributor = new ApprovalEventContributor;
                    $approvalEventContributor->approval_event_component_id = $approvalEventComponent->id;
                    $approvalEventContributor->approvable_id = $contributor->approvable_id;
                    $approvalEventContributor->approvable_type = $contributor->approvable_type;
                    $approvalEventContributor->save();
                }
            }
        }

        return $approvalEvent;
    }

    /**
     * Evaluate if a condition is met by the current model instance.
     *
     * @param  ApprovalCondition  $condition  The condition to evaluate
     * @return bool True if the condition is met, false otherwise
     *
     * @throws RuntimeException If a model instance is not available
     */
    private function evaluateCondition(ApprovalCondition $condition): bool
    {
        if (! $this->modelInstance) {
            throw new RuntimeException('Model instance is not available for condition evaluation.');
        }

        $field = $condition->field;
        $operator = $condition->operator;
        $value = $condition->value;

        // Check if the field exists in the model
        if (! isset($this->modelInstance->{$field}) && ! $this->modelInstance->relationLoaded(Str::camel($field)) && ! method_exists($this->modelInstance, Str::camel($field))) {
            return false;
        }

        $modelValue = $this->modelInstance->{$field};

        switch ($operator) {
            case '=':
            case '==':
                return $modelValue == $value;
            case '!=':
            case '<>':
                return $modelValue != $value;
            case '>':
                return $modelValue > $value;
            case '>=':
                return $modelValue >= $value;
            case '<':
                return $modelValue < $value;
            case '<=':
                return $modelValue <= $value;
            case 'in':
                $arrayValue = json_decode($value, true);

                return is_array($arrayValue) && in_array($modelValue, $arrayValue);
            case 'not in':
                $arrayValue = json_decode($value, true);

                return is_array($arrayValue) && ! in_array($modelValue, $arrayValue);
            default:
                return false;
        }
    }

    /**
     * Verify that an approval workflow exists for the current model type.
     *
     * @return Approval The found approval workflow
     *
     * @throws InvalidArgumentException If no approval workflow exists
     */
    private function ensureMethodExists(): Approval
    {
        $approval = Approval::whereHas('flow.components', fn (mixed $builder) => $builder->where('model', $this->module_type))->first();
        if (! $approval) {
            throw new InvalidArgumentException("Model '$this->module_type' not found in any approval flow. Please check your configuration and try again.");
        }

        return $approval;
    }

    /**
     * Verify that an approval event exists for the current model.
     *
     * @return ApprovalEvent The found approval event
     *
     * @throws InvalidArgumentException If no approval event exists or model is not allowed
     */
    private function ensureModelExists(): ApprovalEvent
    {
        $approvalEvent = ApprovalEvent::where('requestable_type', $this->module_type)->where('requestable_id', $this->module_id)->first();
        if (! $approvalEvent) {
            throw new InvalidArgumentException("Approval event for model '$this->module_type' with ID '$this->module_id' not found.");
        }

        if (in_array($this->module_type, config('lara_approve.models', []))) {
            throw new InvalidArgumentException("Model '$this->module_type' is not allowed to be approved. Please check your configuration and try again.");
        }

        return $approvalEvent;
    }
}
