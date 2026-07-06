<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\SyncDeviceAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AttendanceController extends Controller
{
    public function syncDeviceData(Request $request, Device $device, SyncDeviceAttendance $syncService): StreamedResponse
    {
        return response()->stream(function () use ($device, $syncService) {
            // Disable buffering so progress events reach the browser immediately.
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');

            echo json_encode([
                'type' => 'status',
                'message' => 'Fetching attendance data...',
            ])."\n";
            @ob_flush();
            flush();

            try {
                $result = $syncService->sync($device, function (array $progress) {
                    echo json_encode([
                        'type' => 'progress',
                        ...$progress,
                    ])."\n";
                    @ob_flush();
                    flush();
                });

                if (! $result['ok']) {
                    echo json_encode([
                        'type' => 'error',
                        'message' => $result['message'],
                    ])."\n";
                    @ob_flush();
                    flush();

                    return;
                }

                echo json_encode([
                    'type' => 'complete',
                    ...$result,
                ])."\n";
                @ob_flush();
                flush();
            } catch (Throwable $e) {
                Log::error('Attendance import failed.', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);

                echo json_encode([
                    'type' => 'error',
                    'message' => 'Import failed: '.$e->getMessage(),
                ])."\n";
                @ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
