<?php

namespace App\Enums;

enum InterviewMode: string
{
    case Practice    = 'practice';
    case Simulation  = 'simulation';
    case Challenging = 'challenging';

    public function label(): string
    {
        return match($this) {
            self::Practice    => 'Practice',
            self::Simulation  => 'Simulation',
            self::Challenging => 'Challenging',
        };
    }

    public function defaultQuestionCount(): int
    {
        return match($this) {
            self::Practice    => 5,
            self::Simulation  => 8,
            self::Challenging => 10,
        };
    }
}
