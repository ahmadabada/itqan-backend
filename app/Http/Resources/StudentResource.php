<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'national_id'      => $this->national_id,
            'first_name'       => $this->first_name,
            'second_name'      => $this->second_name,
            'third_name'       => $this->third_name,
            'family_name'      => $this->family_name,
            'full_name'        => $this->fullName(),
            'has_approved_exam' => $this->when(isset($this->has_approved_exam), fn() => (bool) $this->has_approved_exam),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
