<?php

namespace App\Livewire\Admin\Devices;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

// Admin lists registered devices and issues commands (currently only wipe_data).
// The command is enqueued; the mobile picks it up via FCM or the /sync/commands poll.
#[Layout('layouts.admin')]
#[Title('الأجهزة')]
class Index extends Component
{
    use WithPagination;

    public ?int   $wipeDeviceId = null;
    public string $wipeReason   = '';

    public function mount(): void
    {
        if (Auth::user()->role === UserRole::Examiner) {
            $this->redirect(route('examiner.dashboard'));
        }
    }

    public function openWipeModal(int $deviceId): void
    {
        $this->wipeDeviceId = $deviceId;
        $this->wipeReason   = '';
    }

    public function closeWipeModal(): void
    {
        $this->wipeDeviceId = null;
        $this->wipeReason   = '';
    }

    public function issueWipe(): void
    {
        if ($this->wipeDeviceId === null) {
            return;
        }

        $device = Device::findOrFail($this->wipeDeviceId);

        // Avoid duplicate pending commands for the same device.
        $existing = DeviceCommand::pending()
            ->forDevice($device->device_uuid)
            ->where('command_type', DeviceCommand::TYPE_WIPE_DATA)
            ->exists();

        if ($existing) {
            $this->dispatch('notify', type: 'error', message: 'يوجد أمر مسح معلق بالفعل لهذا الجهاز.');
            $this->closeWipeModal();
            return;
        }

        // Auth::id() returns national_id here (User::getAuthIdentifierName is overridden).
        // FK columns reference users.id, so we must read ->id off the user explicitly.
        $adminId = Auth::user()->id;

        $command = DeviceCommand::create([
            'device_uuid'        => $device->device_uuid,
            'command_type'       => DeviceCommand::TYPE_WIPE_DATA,
            'payload'            => $this->wipeReason ? ['reason' => $this->wipeReason] : null,
            'issued_by_admin_id' => $adminId,
            'issued_at'          => now(),
            'status'             => DeviceCommand::STATUS_PENDING,
        ]);

        AuditLog::create([
            'user_id'     => $adminId,
            'action'      => 'device_wipe_issued',
            'target_type' => 'device_command',
            'target_id'   => $command->id,
            'new_values'  => [
                'device_uuid' => $device->device_uuid,
                'reason'      => $this->wipeReason ?: null,
            ],
        ]);

        $this->closeWipeModal();
        $this->dispatch('notify', type: 'success', message: 'تم إصدار أمر المسح.');
    }

    public function render()
    {
        $devices = Device::query()
            ->with('lastUser:id,first_name,family_name')
            ->withCount([
                'commands as pending_commands_count' => fn($q) => $q
                    ->where('status', DeviceCommand::STATUS_PENDING),
            ])
            ->orderByDesc('last_seen_at')
            ->paginate(25);

        return view('livewire.admin.devices.index', [
            'devices' => $devices,
        ]);
    }
}
