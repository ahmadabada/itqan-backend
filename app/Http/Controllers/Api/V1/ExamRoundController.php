<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ExamRound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamRoundController extends Controller
{
    // PATCH /exam-rounds/{examRound}/rename
    public function rename(Request $request, ExamRound $examRound): JsonResponse
    {
        $user = $request->user();
        abort_if(
            $user->role !== UserRole::SuperAdmin && ! $user->is_super_admin,
            403,
            'هذه العملية متاحة للسوبر أدمن فقط.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('exam_rounds', 'name')->ignore($examRound->id),
            ],
        ], [
            'name.required' => 'اسم الجولة مطلوب.',
            'name.unique' => 'اسم الجولة مستخدم مسبقا.',
        ]);

        $oldName = $examRound->name;
        $newName = trim($validated['name']);

        if ($oldName !== $newName) {
            $examRound->update(['name' => $newName]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'exam_round_renamed',
                'target_type' => 'exam_round',
                'target_id' => $examRound->id,
                'old_values' => ['name' => $oldName],
                'new_values' => ['name' => $newName],
            ]);
        }

        return response()->json([
            'round' => [
                'id' => $examRound->id,
                'name' => $examRound->name,
            ],
            'renamed' => $oldName !== $examRound->name,
        ]);
    }
}
