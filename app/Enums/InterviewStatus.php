<?php

namespace App\Enums;

enum InterviewStatus: string
{
    case Pending     = 'pending';
    case Analyzing   = 'analyzing';
    case Interviewing = 'interviewing';
    case Evaluating  = 'evaluating';
    case Completed   = 'completed';
    case Failed      = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending      => 'Pending',
            self::Analyzing    => 'Analyzing Job',
            self::Interviewing => 'In Progress',
            self::Evaluating   => 'Evaluating',
            self::Completed    => 'Completed',
            self::Failed       => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed]);
    }
}
