<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncExamsRequest;
use App\Models\DeviceCommand;
use App\Models\Exam;
use App\Models\ReexamPermit;
use App\Models\Student;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SyncController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    // POST /sync/exams — Flutter uploads completed offline exams. Each exam carries
    // its own student object and client_request_id for idempotent retries.
    public function syncExams(SyncExamsRequest $request): JsonResponse
    {
        $results = $this->syncService->processExams(
            $request->validated()['exams'],
            $request->user()->id,
        );

        return response()->json(['results' => $results]);
    }

    // GET /sync/status
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'pending_reviews_count' => Exam::where('status', ExamStatus::PendingReview)->count(),
            'server_time'           => now()->toISOString(),
            'students_last_updated' => Student::max('updated_at'),
            'permits_last_updated'  => ReexamPermit::max('created_at'),
        ]);
    }

    // GET /sync/commands?device_uuid=... — fallback for missed FCM pushes.
    // The device polls this on every successful sync.
    public function commands(Request $request): JsonResponse
    {
        $deviceUuid = $request->validate([
            'device_uuid' => ['required', 'string', 'max:64'],
        ])['device_uuid'];

        $commands = DeviceCommand::pending()
            ->forDevice($deviceUuid)
            ->orderBy('issued_at')
            ->get(['id', 'command_type', 'payload', 'issued_at']);

        return response()->json(['commands' => $commands]);
    }

    // POST /sync/commands/{deviceCommand}/ack
    // Device reports the outcome of executing an admin command (wipe_data, etc.).
    public function ackCommand(Request $request, DeviceCommand $deviceCommand): JsonResponse
    {
        $validated = $request->validate([
            'status'         => ['required', Rule::in(['completed', 'failed', 'acknowledged'])],
            'failure_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $deviceCommand->forceFill([
            'status'          => $validated['status'],
            'acknowledged_at' => $deviceCommand->acknowledged_at ?? now(),
            'completed_at'    => $validated['status'] === 'completed' ? now() : $deviceCommand->completed_at,
            'failure_reason'  => $validated['failure_reason'] ?? null,
        ])->save();

        return response()->json(['acknowledged' => true]);
    }
}
