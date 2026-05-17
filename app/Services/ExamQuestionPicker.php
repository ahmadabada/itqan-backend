<?php

namespace App\Services;

use App\Enums\QuestionGroup;
use App\Models\RecitationQuestion;
use InvalidArgumentException;
use RuntimeException;

/**
 * Picks 3 recitation questions for a new exam.
 *
 * BR-EXAM-10: Full-Quran picks one question from each of the 3 fixed group pairs
 *             (م1∨م2), (م3∨م4), (م5∨م6) — pair side is chosen randomly.
 * BR-EXAM-11: Half-Quran picks one question per examiner-chosen group; the same
 *             group may not be chosen twice in the same exam.
 */
class ExamQuestionPicker
{
    /**
     * @return array<int, array{group: QuestionGroup, question: RecitationQuestion}>
     */
    public function pickForFullQuran(): array
    {
        $picked = [];

        foreach (QuestionGroup::fullQuranPairs() as $pair) {
            $group = $pair[random_int(0, 1)];
            $picked[] = [
                'group'    => $group,
                'question' => $this->randomFromGroup($group, excludeIds: $this->idsOf($picked)),
            ];
        }

        return $picked;
    }

    /**
     * @param  array<int, int>  $groupNumbers exactly 3 entries in 1..6, no duplicates (BR-EXAM-11).
     * @return array<int, array{group: QuestionGroup, question: RecitationQuestion}>
     */
    public function pickForHalfQuran(array $groupNumbers): array
    {
        if (count($groupNumbers) !== 3) {
            throw new InvalidArgumentException('half_quran requires exactly 3 group selections.');
        }

        if (count(array_unique($groupNumbers)) !== 3) {
            throw new InvalidArgumentException('half_quran selected_groups must be 3 distinct groups.');
        }

        $picked = [];

        foreach ($groupNumbers as $num) {
            $group = QuestionGroup::from((int) $num);
            $picked[] = [
                'group'    => $group,
                'question' => $this->randomFromGroup($group, excludeIds: $this->idsOf($picked)),
            ];
        }

        return $picked;
    }

    private function randomFromGroup(QuestionGroup $group, array $excludeIds = []): RecitationQuestion
    {
        $question = RecitationQuestion::query()
            ->active()
            ->where('group_number', $group->value)
            ->when($excludeIds, fn($q) => $q->whereNotIn('id', $excludeIds))
            ->inRandomOrder()
            ->first();

        if (! $question) {
            throw new RuntimeException("لا توجد أسئلة متاحة في {$group->shortLabel()}.");
        }

        return $question;
    }

    /** @param array<int, array{question: RecitationQuestion}> $picked */
    private function idsOf(array $picked): array
    {
        return array_map(fn($p) => $p['question']->id, $picked);
    }
}
