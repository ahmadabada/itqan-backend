<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterDeviceRequest;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    // POST /api/v1/devices/register
    // Single row per physical device; fcm_token and last_user_id are upserted on
    // every login so a shared device tracks whichever examiner is currently signed in.
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $device = Device::updateOrCreate(
            ['device_uuid' => $data['device_uuid']],
            [
                'fcm_token'    => $data['fcm_token']    ?? null,
                'fcm_platform' => $data['fcm_platform'] ?? null,
                'last_user_id' => $user->id,
                'last_seen_at' => now(),
            ],
        );

        $user->forceFill([
            'fcm_token'             => $data['fcm_token']    ?? $user->fcm_token,
            'fcm_platform'          => $data['fcm_platform'] ?? $user->fcm_platform,
            'last_mobile_login_at'  => now(),
        ])->save();

        return response()->json([
            'device_id'   => $device->id,
            'device_uuid' => $device->device_uuid,
            'registered'  => true,
        ]);
    }
}
