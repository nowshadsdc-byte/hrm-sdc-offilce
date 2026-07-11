<?php

namespace App\Services;

use App\Models\Device;
use App\Models\RawDeviceData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SmartAttendanceSyncService
{
    public function __construct(
        protected SyncToday $syncToday,
        protected AttendanceSyncService $attendanceSyncService,
    ) {}

    /**
     * Smart sync: if today, pull fresh data; then sync if new data exists.
     *
     * @param  callable(array):void|null  $onProgress
     * @return array{ok:bool,pulled_new_data:bool,total_groups:int,created:int,updated:int,unchanged:int,message:string}
     */
    public function sync(Carbon $selectedDate, ?callable $onProgress = null): array
    {
        $dateString = $selectedDate->toDateString();
        $isToday = $selectedDate->toDateString() === now()->toDateString();

        $pulledNewData = false;

        // If today, pull fresh data from all devices
        if ($isToday) {
            if ($onProgress !== null) {
                $onProgress([
                    'type' => 'status',
                    'message' => 'Pulling today\'s attendance data from devices...',
                ]);
            }

            try {
                $devices = Device::query()->get();

                foreach ($devices as $device) {
                    $result = $this->syncToday->sync($device, $onProgress);

                    if ($result['ok'] && $result['inserted'] > 0) {
                        $pulledNewData = true;
                    }
                }

                if ($onProgress !== null) {
                    $onProgress([
                        'type' => 'status',
                        'message' => 'Device data pull completed. '.($pulledNewData ? 'New data found.' : 'No new data found.'),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Smart sync device pull failed', [
                    'date' => $dateString,
                    'error' => $e->getMessage(),
                ]);

                if ($onProgress !== null) {
                    $onProgress([
                        'type' => 'warning',
                        'message' => 'Device data pull encountered an issue, continuing with existing data.',
                    ]);
                }
            }
        }

        // Check if there is any raw data for this date
        $rawDataCount = RawDeviceData::query()
            ->where(function ($query) use ($dateString) {
                $query->whereDate('recordTime', $dateString)
                    ->orWhere(function ($nested) use ($dateString) {
                        $nested->whereNull('recordTime')
                            ->whereDate('date', $dateString);
                    });
            })
            ->count();

        if ($rawDataCount === 0) {
            return [
                'ok' => true,
                'pulled_new_data' => $pulledNewData,
                'total_groups' => 0,
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'message' => 'No raw punch records found for '.$dateString,
            ];
        }

        // Sync the attendance from raw data
        if ($onProgress !== null) {
            $onProgress([
                'type' => 'status',
                'message' => 'Processing raw punch data and updating attendance table...',
            ]);
        }

        $syncResult = $this->attendanceSyncService->syncForDate($selectedDate);

        if ($onProgress !== null) {
            $onProgress([
                'type' => 'complete',
                'message' => 'Attendance sync completed successfully.',
                'created' => $syncResult['created'],
                'updated' => $syncResult['updated'],
                'unchanged' => $syncResult['unchanged'],
                'total_groups' => $syncResult['total_groups'],
            ]);
        }

        return [
            'ok' => true,
            'pulled_new_data' => $pulledNewData,
            'total_groups' => $syncResult['total_groups'],
            'created' => $syncResult['created'],
            'updated' => $syncResult['updated'],
            'unchanged' => $syncResult['unchanged'],
            'message' => sprintf(
                'Sync completed: %d created, %d updated, %d unchanged from %d employee records',
                $syncResult['created'],
                $syncResult['updated'],
                $syncResult['unchanged'],
                $syncResult['total_groups']
            ),
        ];
    }
}
