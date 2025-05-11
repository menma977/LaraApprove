<?php

namespace Menma977\Larapprove\Traits;

trait EvaluatesCondition
{
    /**
     * Evaluates a given condition against the provided request data.
     *
     * @param array|null $condition An associative array defining the condition. The array should include keys such as 'field', 'operator', and 'value'. If null, the method returns true.
     * @param array $requestData An array of data to evaluate the condition against.
     *
     * @return bool Returns true if the condition is met, otherwise false.
     */
    public function evaluateCondition(?array $condition, array $requestData): bool
    {
        if (is_null($condition)) {
            return true;
        }

        $fieldValue = data_get($requestData, $condition['field']);

        return match ($condition['operator']) {
            '>' => $fieldValue > $condition['value'],
            '>=' => $fieldValue >= $condition['value'],
            '<' => $fieldValue < $condition['value'],
            '<=' => $fieldValue <= $condition['value'],
            '=' => $fieldValue == $condition['value'],
            '!=' => $fieldValue != $condition['value'],
            default => false,
        };
    }
}