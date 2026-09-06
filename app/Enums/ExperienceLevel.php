<?php

namespace App\Enums;

enum ExperienceLevel: string
{
    case Junior   = 'junior';
    case Mid      = 'mid';
    case Senior   = 'senior';
    case Lead     = 'lead';

    public function label(): string
    {
        return match($this) {
            self::Junior => 'Junior (0–2 years)',
            self::Mid    => 'Mid-level (2–5 years)',
            self::Senior => 'Senior (5–8 years)',
            self::Lead   => 'Lead / Principal (8+ years)',
        };
    }
}
