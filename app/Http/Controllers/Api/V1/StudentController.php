<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // BR-SYNC-01: Returns all 600 students, supports delta sync via updated_since
    public function index(Request $request): JsonResponse
    {
        $query = Student::query()->orderBy('family_name')->orderBy('first_name');

        if ($request->has('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        $students = $query->get();

        // Batch check approved exam status to avoid N+1
        $approvedStudentIds = Exam::whereIn('student_id', $students->pluck('id'))
            ->where('is_approved', true)
            ->pluck('student_id')
            ->flip();

        $students->each(function (Student $s) use ($approvedStudentIds) {
            $s->has_approved_exam = $approvedStudentIds->has($s->id);
        });

        return response()->json([
            'students'    => StudentResource::collection($students),
            'total'       => $students->count(),
            'server_time' => now()->toISOString(),
        ]);
    }

    // BR-STD-04: Examiner can add student manually
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'national_id'  => ['required', 'string'],
            'first_name'   => ['required', 'string', 'max:50'],
            'second_name'  => ['nullable', 'string', 'max:50'],
            'third_name'   => ['nullable', 'string', 'max:50'],
            'family_name'  => ['required', 'string', 'max:50'],
        ]);

        // BR-STD-01: national_id must be unique
        $existing = Student::where('national_id', $request->national_id)->first();
        if ($existing) {
            return response()->json([
                'error'            => 'duplicate_national_id',
                'message'          => 'رقم الهوية موجود مسبقاً.',
                'existing_student' => [
                    'id'        => $existing->id,
                    'full_name' => $existing->fullName(),
                ],
            ], 409);
        }

        $student = Student::create($request->only([
            'national_id', 'first_name', 'second_name', 'third_name', 'family_name',
        ]));

        return response()->json(['student' => new StudentResource($student)], 201);
    }
}
