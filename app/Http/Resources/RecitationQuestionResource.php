<?php

namespace App\Http\Resources;

use App\Support\Surah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecitationQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'question_number' => $this->question_number,
            'group_number'    => $this->group_number->value,
            'group_label'     => $this->group_number->shortLabel(),
            'start' => [
                'surah_number' => (int) $this->start_surah,
                'surah_name'   => Surah::nameFor((int) $this->start_surah),
                'ayah'         => (int) $this->start_ayah,
                'page'         => (int) $this->start_page,
            ],
            'end' => [
                'surah_number' => (int) $this->end_surah,
                'surah_name'   => Surah::nameFor((int) $this->end_surah),
                'ayah'         => (int) $this->end_ayah,
                'page'         => (int) $this->end_page,
            ],
        ];
    }
}
