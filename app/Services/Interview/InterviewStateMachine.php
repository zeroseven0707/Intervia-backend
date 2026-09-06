<?php

namespace App\Services\Interview;

use App\Enums\InterviewStatus;
use App\Models\InterviewSession;
use Exception;

class InterviewStateMachine
{
    /**
     * Valid transitions: from -> [allowed to states]
     */
    private const TRANSITIONS = [
        InterviewStatus::Pending->value      => [InterviewStatus::Analyzing->value],
        InterviewStatus::Analyzing->value    => [InterviewStatus::Interviewing->value, InterviewStatus::Failed->value],
        InterviewStatus::Interviewing->value => [InterviewStatus::Evaluating->value, InterviewStatus::Failed->value],
        InterviewStatus::Evaluating->value   => [InterviewStatus::Completed->value, InterviewStatus::Interviewing->value, InterviewStatus::Failed->value],
        InterviewStatus::Completed->value    => [],
        InterviewStatus::Failed->value       => [InterviewStatus::Analyzing->value], // allow restart
    ];

    public function transition(InterviewSession $session, InterviewStatus $to): void
    {
        $from    = $session->status->value;
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (!in_array($to->value, $allowed)) {
            throw new Exception(
                "Invalid interview state transition: {$from} → {$to->value}"
            );
        }

        $session->update(['status' => $to]);
    }

    public function can(InterviewSession $session, InterviewStatus $to): bool
    {
        $allowed = self::TRANSITIONS[$session->status->value] ?? [];
        return in_array($to->value, $allowed);
    }
}
