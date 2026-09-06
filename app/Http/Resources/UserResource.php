<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'role'               => $this->role?->value ?? 'user',
            'experience_level'   => $this->experience_level?->value,
            'target_position_id' => $this->target_position_id,
            'target_position'    => $this->whenLoaded('targetPosition', fn() => [
                'id'   => $this->targetPosition->id,
                'name' => $this->targetPosition->name,
                'slug' => $this->targetPosition->slug,
            ]),
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
