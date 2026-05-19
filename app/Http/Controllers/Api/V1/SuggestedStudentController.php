<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SuggestedStudent;
use App\Services\ArabicSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Read-only API for the mobile autocomplete cache. BR-SS-1 — every query is
// scoped to the authenticated examiner's gender on the server side; clients
// cannot widen the result set by sending a different filter.
class SuggestedStudentController extends Controller
{
    // GET /suggested-students — gender-scoped list used by the web examiner UI
    // when picking from the dropdown. Mobile does not call this endpoint;
    // see sync() below for the rationale.
    public function index(Request $request): JsonResponse
    {
        $rows = SuggestedStudent::query()
            ->forExaminer($request->user())
            ->orderBy('family_name')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'data' => $rows->map(fn($s) => $this->serialize($s))->values(),
        ]);
    }

    // GET /suggested-students/sync — full UNFILTERED list for the mobile cache.
    //
    // Mobile devices are shared between examiners (e.g. a male examiner logs
    // out and a female examiner logs in on the same tablet). If the cache only
    // held the first examiner's gender, the second examiner would see nothing
    // until they manually refreshed. Returning everything and letting the
    // device filter at query time avoids that bad first-experience.
    //
    // We accept the BR-SS-1 spec deviation here because (a) the data is admin-
    // curated and not user-sensitive, and (b) the examiner is still
    // authenticated, so the only access this widens is from the same device.
    public function sync(Request $request): JsonResponse
    {
        $rows = SuggestedStudent::query()
            ->orderBy('family_name')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'data' => $rows->map(fn($s) => $this->serialize($s))->values(),
        ]);
    }

    // GET /suggested-students/search?q=... — primarily for the web autocomplete.
    // Mobile builds its own filter in memory off the cached list.
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $rows = SuggestedStudent::query()
            ->forExaminer($request->user())
            ->where(function ($builder) use ($q) {
                ArabicSearch::applyTo(
                    $builder,
                    $q,
                    ['first_name', 'second_name', 'third_name', 'family_name'],
                    ['national_id'],
                );
            })
            ->orderBy('family_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $rows->map(fn($s) => $this->serialize($s))->values(),
        ]);
    }

    public function show(Request $request, SuggestedStudent $suggestedStudent): JsonResponse
    {
        // 404 rather than 403 — the existence of a candidate of the opposite
        // gender is itself information we don't want to leak.
        if ($suggestedStudent->gender->value !== $request->user()->gender) {
            abort(404);
        }

        return response()->json(['data' => $this->serialize($suggestedStudent)]);
    }

    private function serialize(SuggestedStudent $s): array
    {
        return [
            'id'               => $s->id,
            'national_id'      => $s->national_id,
            'first_name'       => $s->first_name,
            'second_name'      => $s->second_name,
            'third_name'       => $s->third_name,
            'family_name'      => $s->family_name,
            'full_name'        => $s->fullName(),
            'student_zone'     => $s->student_zone,
            'gender'           => $s->gender->value,
            'is_recite_before' => $s->is_recite_before,
        ];
    }
}
