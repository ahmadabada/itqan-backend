<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // GET /students — the roster the device downloads and picks from. Students
    // are created only on the server, so this is read-only for mobile; there is
    // no store endpoint any more.
    //
    // Returns every (non-deleted) student with gender + halaqah. Devices are
    // shared across examiners, so the app pulls the whole list once and filters
    // to the logged-in examiner's gender locally at pick time. `updated_since`
    // is offered for delta polling, but the primary path is a full re-download
    // (delete-then-insert) so server-side deletions propagate.
    public function index(Request $request): JsonResponse
    {
        $query = Student::query()
            ->orderBy('family_name')
            ->orderBy('first_name');

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        $students = $query->get();

        return response()->json([
            'students'    => StudentResource::collection($students),
            'total'       => $students->count(),
            'server_time' => now()->toISOString(),
        ]);
    }
}
