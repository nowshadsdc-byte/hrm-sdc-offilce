@extends('tyro-dashboard::layouts.admin')

@section('title', 'Devices')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Devices</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Biometric Devices</h1>
            <p class="page-description">Manage fingerprint and face recognition devices.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAddDeviceModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Device
        </button>
    </div>
</div>

{{-- Statistics Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="border-left: 4px solid var(--primary);">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Total Devices</p>
            <p style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-top: 0.5rem;">{{ $stats['total'] }}</p>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--success);">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Online</p>
            <p style="font-size: 2rem; font-weight: 700; color: var(--success); margin-top: 0.5rem;">{{ $stats['online'] }}</p>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--warning);">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Offline</p>
            <p style="font-size: 2rem; font-weight: 700; color: var(--warning); margin-top: 0.5rem;">{{ $stats['offline'] }}</p>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Error</p>
            <p style="font-size: 2rem; font-weight: 700; color: var(--danger); margin-top: 0.5rem;">{{ $stats['error'] }}</p>
        </div>
    </div>
</div>

{{-- Devices List --}}
<div style="margin-bottom: 2rem;">

    <div style="padding: 1.5rem;">
        @if(count($devices) > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                @foreach($devices as $device)
                <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; background: white; color: #000; position: relative; display: flex; flex-direction: column;">
                    {{-- Header with Title and Status Badge --}}
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0; color: #000;">{{ $device->name }}</h4>
                            <p style="font-size: 0.75rem; color: #6b7280; margin: 0.25rem 0 0; display: flex; align-items: center; gap: 0.25rem;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 0.85rem; height: 0.85rem;">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                {{ $device->location ?? 'Not Specified' }}
                            </p>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.625rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;
                            @if($device->status === 'online')
                                background: #d1fae5;
                                color: #065f46;
                            @elseif($device->status === 'offline')
                                background: #fef3c7;
                                color: #92400e;
                            @else
                                background: #fee2e2;
                                color: #991b1b;
                            @endif
                        ">
                            <span style="display: inline-block; width: 0.375rem; height: 0.375rem; border-radius: 50%; background: currentColor;"></span>
                            {{ ucfirst($device->status) }}
                        </div>
                    </div>

                    {{-- Device Icon --}}
                    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            @if($device->device_type === 'fingerprint')
                                👆
                            @else
                                👁️
                            @endif
                        </div>
                    </div>

                    {{-- Details Grid --}}
                    <div style="flex: 1; display: grid; gap: 0.875rem; margin-bottom: 1rem; font-size: 0.875rem;">
                        {{-- IP Address and Port --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">IP ADDRESS</p>
                                <p style="font-size: 0.875rem; font-weight: 600; color: #1e40af; margin: 0;">{{ $device->connection_type === 'api' ? $device->api_endpoint : $device->ip_address }}</p>
                            </div>
                            <div>
                                <p style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">PORT</p>
                                <p style="font-size: 0.875rem; font-weight: 600; color: #1e40af; margin: 0;">{{ $device->port }}</p>
                            </div>
                        </div>

                        {{-- Serial Number and Employees --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">SERIAL NUMBER</p>
                                <p style="font-size: 0.875rem; font-weight: 600; color: #000; margin: 0;">{{ $device->serial_number }}</p>
                            </div>
                            <div>
                                <p style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">EMPLOYEES</p>
                                <p style="font-size: 0.875rem; font-weight: 600; color: #000; margin: 0;">🔄 0</p>
                            </div>
                        </div>

                        {{-- Sync Interval and Auto-Sync --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">SYNC INTERVAL</p>
                                <p style="font-size: 0.875rem; font-weight: 600; color: #000; margin: 0;">Every {{ $device->sync_interval_minutes }} min</p>
                            </div>
                            <div>
                                <p style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">AUTO-SYNC</p>
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    @if($device->auto_sync)
                                        <div style="width: 1.5rem; height: 0.875rem; background: #10b981; border-radius: 0.4375rem; position: relative;">
                                            <div style="position: absolute; right: 0.125rem; top: 0.125rem; width: 0.625rem; height: 0.625rem; background: white; border-radius: 50%;"></div>
                                        </div>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: #10b981;">On</span>
                                    @else
                                        <div style="width: 1.5rem; height: 0.875rem; background: #e5e7eb; border-radius: 0.4375rem; position: relative;">
                                            <div style="position: absolute; left: 0.125rem; top: 0.125rem; width: 0.625rem; height: 0.625rem; background: white; border-radius: 50%;"></div>
                                        </div>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: #6b7280;">Off</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Last Sync --}}
                        <div style="display: flex; align-items: center; gap: 0.375rem; padding-top: 0.5rem; border-top: 1px solid #e5e7eb;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 0.875rem; height: 0.875rem; color: #6b7280;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span style="font-size: 0.75rem; color: #6b7280;">Last Sync <strong style="color: #000;">{{ $device->last_sync_at ? $device->last_sync_at->diffForHumans() : 'Never' }}</strong></span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button type="button" onclick="syncDeviceUsers(this, {{ $device->id }}, @js($device->name))" style="flex: 1; padding: 0.5rem; background: #10b981; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.375rem; transition: all 0.2s;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <path d="M20.49 15a9 9 0 1 1-2-8.12"></path>
                            </svg>
                            <span>Sync Now</span>
                        </button>
                        <button type="button" onclick="syncTodayData(this, {{ $device->id }}, @js($device->name))" style="padding: 0.5rem 0.75rem; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.375rem; transition: all 0.2s;" title="Sync Today">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Sync Today
                        </button>
                        <button type="button" onclick="importDeviceData(this, {{ $device->id }}, @js($device->name))" style="padding: 0.5rem 0.75rem; background: white; color: #6b7280; border: 1px solid #d1d5db; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.375rem; transition: all 0.2s;" title="Import Device Data">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline stroke-linecap="round" stroke-linejoin="round" points="7 10 12 15 17 10"></polyline>
                                <line stroke-linecap="round" stroke-linejoin="round" x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Import Device Data
                        </button>
                        <button type="button" onclick="testDevice({{ $device->id }}, @js($device->name))" style="padding: 0.5rem 0.75rem; background: white; color: #6b7280; border: 1px solid #d1d5db; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.375rem; transition: all 0.2s;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <path d="M9.59 0h1.42A9.46 9.46 0 0 1 21 10.13v.5a9.46 9.46 0 0 1-10 9.37h-1.42A9.46 9.46 0 0 1 -1 10.63v-.5A9.46 9.46 0 0 1 9.59 0z" opacity="0.5"></path>
                                <circle cx="12" cy="10" r="2"></circle>
                            </svg>
                            Test
                        </button>
                        <button type="button" onclick="openEditDeviceModal({{ $device->id }}, @js($device->name), @js($device->device_type), @js($device->connection_type ?? 'adms'), @js($device->ip_address), @js($device->api_endpoint), @js($device->api_url), {{ $device->port }}, @js($device->serial_number), @js($device->location), {{ $device->sync_interval_minutes }}, {{ $device->auto_sync ? 'true' : 'false' }})" style="padding: 0.5rem 0.75rem; background: white; color: #6b7280; border: 1px solid #d1d5db; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.375rem; transition: all 0.2s;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <circle cx="12" cy="12" r="1"></circle>
                                <circle cx="19" cy="12" r="1"></circle>
                                <circle cx="5" cy="12" r="1"></circle>
                            </svg>
                        </button>
                        <button type="button" onclick="confirmDelete({{ $device->id }}, @js($device->name))" style="padding: 0.5rem 0.75rem; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.375rem; transition: all 0.2s;" title="Delete Device">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div style="padding: 3rem 1rem; text-align: center; color: var(--muted);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 3rem; height: 3rem; margin: 0 auto 1rem; opacity: 0.5;">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                <p style="font-size: 1rem; font-weight: 500;">No devices found.</p>
            </div>
        @endif
    </div>
</div>

{{-- Import Progress Modal --}}
<div id="importProgressModal" style="display: none; position: fixed; inset: 0; z-index: 9500; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 0.875rem; width: 100%; max-width: 34rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Import Device Data</h3>
            <button type="button" onclick="closeImportProgressModal()" style="border: none; background: none; font-size: 1.25rem; line-height: 1; color: #6b7280; cursor: pointer;">×</button>
        </div>
        <div style="padding: 1rem 1.25rem;">
            <p id="importProgressStatus" style="margin: 0 0 0.75rem; font-size: 0.875rem; color: #374151;">Waiting to start import...</p>
            <div style="height: 0.625rem; background: #e5e7eb; border-radius: 999px; overflow: hidden;">
                <div id="importProgressBar" style="height: 100%; width: 0%; background: linear-gradient(90deg, #10b981, #059669); transition: width 0.2s ease;"></div>
            </div>
            <p id="importProgressPercent" style="margin: 0.5rem 0 0; font-size: 0.75rem; color: #6b7280;">0%</p>
            <pre id="importProgressLog" style="margin-top: 0.9rem; max-height: 240px; overflow-y: auto; background: #0f172a; color: #e2e8f0; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; line-height: 1.4;">Fetching attendance data...</pre>
        </div>
    </div>
</div>

{{-- Add Device Modal --}}
<div id="addDeviceModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 0.75rem; width: 100%; max-width: 28rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="font-size: 1rem; font-weight: 600; margin: 0; color: #000;">Add Biometric Device</h3>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0;">Register a new fingerprint attendance device.</p>
            </div>
            <button type="button" onclick="closeAddDeviceModal()" style="background: none; border: none; font-size: 1.5rem; color: #6b7280; cursor: pointer; padding: 0; width: 1.5rem; height: 1.5rem; display: flex; align-items: center; justify-content: center;">×</button>
        </div>

        <form id="addDeviceForm" method="POST" action="{{ route('devices.store') }}" style="padding: 1.5rem;">
            @csrf

            {{-- Device Name (Full Width) --}}
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="deviceName" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">Device Name <span style="color: #10b981;">*</span></label>
                <input type="text" id="deviceName" name="name" class="form-input" placeholder="e.g. Main Entrance Scanner" required style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="deviceType" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">Device Type <span style="color: #10b981;">*</span></label>
                    <select id="deviceType" name="device_type" class="form-input" required style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                        @foreach($deviceTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="connectionType" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">Connection Type <span style="color: #10b981;">*</span></label>
                    <select id="connectionType" name="connection_type" class="form-input" required onchange="toggleAddConnectionFields()" style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                        <option value="adms">ADMS</option>
                        <option value="api">API</option>
                    </select>
                </div>
            </div>

            {{-- ADMS Connection --}}
            <div id="addAdmsFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="ipAddress" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">IP Address <span style="color: #10b981;">*</span></label>
                    <input type="text" id="ipAddress" name="ip_address" class="form-input" placeholder="192.168.1.201" required style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                </div>

                <div class="form-group">
                    <label for="port" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">Port <span style="color: #10b981;">*</span></label>
                    <input type="number" id="port" name="port" class="form-input" value="4370" min="1" max="65535" required style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                </div>
            </div>

            {{-- API Connection --}}
            <div id="addApiFields" style="display: none; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="apiUrl" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">API URL <span style="color: #10b981;">*</span></label>
                    <input type="url" id="apiUrl" name="api_url" class="form-input" placeholder="http://202.59.209.159:4370/api/users" style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="apiEndpoint" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">API Endpoint (Device IP) <span style="color: #10b981;">*</span></label>
                    <input type="text" id="apiEndpoint" name="api_endpoint" class="form-input" placeholder="103.77.61.202" style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                </div>

                <div class="form-group">
                    <label for="apiPort" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">Port <span style="color: #10b981;">*</span></label>
                    <input type="number" id="apiPort" min="1" max="65535" value="4370" style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                </div>
            </div>

            {{-- Serial Number and Location --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="serialNumber" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">Serial Number <span style="color: #10b981;">*</span></label>
                    <input type="text" id="serialNumber" name="serial_number" class="form-input" placeholder="ZK-2024-XXX" required style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                </div>

                <div class="form-group">
                    <label for="location" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">Location</label>
                    <input type="text" id="location" name="location" class="form-input" placeholder="e.g. HQ Lobby" style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                </div>
            </div>

            {{-- Sync Interval --}}
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="syncInterval" class="form-label" style="color: #000; font-weight: 500; display: block; margin-bottom: 0.5rem;">Sync Interval</label>
                <select id="syncInterval" name="sync_interval_minutes" class="form-input" style="border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 0.625rem 0.75rem; width: 100%; font-size: 0.875rem; transition: border-color 0.2s;">
                    <option value="5">Every 5 minutes</option>
                    <option value="10">Every 10 minutes</option>
                    <option value="15">Every 15 minutes</option>
                    <option value="30">Every 30 minutes</option>
                    <option value="60">Every 1 hour</option>
                </select>
            </div>

            {{-- Auto Sync Toggle --}}
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; padding: 0.75rem; background: #f9fafb; border-radius: 0.5rem;">
                <input type="checkbox" id="autoSync" name="auto_sync" value="1" checked style="width: 1.25rem; height: 1.25rem; cursor: pointer; accent-color: #10b981;">
                <div style="flex: 1;">
                    <label for="autoSync" style="cursor: pointer; margin: 0; font-weight: 500; color: #000; display: block;">Auto-Sync</label>
                    <p style="font-size: 0.75rem; color: #6b7280; margin: 0.25rem 0 0;">Automatically fetch attendance data on schedule</p>
                </div>
            </div>

            {{-- Buttons --}}
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="closeAddDeviceModal()" style="padding: 0.625rem 1.25rem; border: 1px solid #d1d5db; background: white; color: #000; border-radius: 0.5rem; font-weight: 500; cursor: pointer; transition: all 0.2s;">Cancel</button>
                <button type="submit" style="padding: 0.625rem 1.5rem; background: #10b981; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">Add Device</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Device Modal --}}
<div id="editDeviceModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 42rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white;">
            <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Edit Device</h3>
        </div>

        <form id="editDeviceForm" method="POST" style="padding: 1.25rem 1.5rem 1.5rem;">
            @csrf
            @method('PUT')
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="editDeviceName" class="form-label">Device Name</label>
                    <input type="text" id="editDeviceName" name="name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="editDeviceType" class="form-label">Device Type</label>
                    <select id="editDeviceType" name="device_type" class="form-input" required>
                        @foreach($deviceTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="editConnectionType" class="form-label">Connection Type</label>
                <select id="editConnectionType" name="connection_type" class="form-input" required onchange="toggleEditConnectionFields()">
                    <option value="adms">ADMS</option>
                    <option value="api">API</option>
                </select>
            </div>

            <div id="editAdmsFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="editIpAddress" class="form-label">IP Address</label>
                    <input type="text" id="editIpAddress" name="ip_address" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="editPort" class="form-label">Port</label>
                    <input type="number" id="editPort" name="port" class="form-input" min="1" max="65535" required>
                </div>
            </div>

            <div id="editApiFields" style="display: none; margin-bottom: 1rem;">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="editApiUrl" class="form-label">API URL</label>
                    <input type="url" id="editApiUrl" name="api_url" class="form-input" placeholder="http://202.59.209.159:4370/api/users">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="editApiEndpoint" class="form-label">API Endpoint (Device IP)</label>
                    <input type="text" id="editApiEndpoint" name="api_endpoint" class="form-input" placeholder="103.77.61.202">
                </div>

                <div class="form-group">
                    <label for="editApiPort" class="form-label">Port</label>
                    <input type="number" id="editApiPort" min="1" max="65535" class="form-input" value="4370">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="editSerialNumber" class="form-label">Serial Number</label>
                    <input type="text" id="editSerialNumber" name="serial_number" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="editLocation" class="form-label">Location</label>
                    <input type="text" id="editLocation" name="location" class="form-input">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="editSyncInterval" class="form-label">Sync Interval (minutes)</label>
                    <input type="number" id="editSyncInterval" name="sync_interval_minutes" class="form-input" min="1" required>
                </div>

                <div class="form-group">
                    <label for="editAutoSync" class="form-label">Auto Sync</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0;">
                        <input type="checkbox" id="editAutoSync" name="auto_sync" value="1" style="width: 1rem; height: 1rem; cursor: pointer;">
                        <label for="editAutoSync" style="cursor: pointer; margin: 0;">Enable automatic sync</label>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" onclick="closeEditDeviceModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Device</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<style>
    #addDeviceModal input:focus,
    #addDeviceModal select:focus {
        outline: none;
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    #editDeviceModal input:focus,
    #editDeviceModal select:focus {
        outline: none;
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
</style>
@endpush

@push('scripts')
<script>
    function openAddDeviceModal() {
        document.getElementById('addDeviceModal').style.display = 'flex';
        document.getElementById('deviceName').focus();
    }

    function closeAddDeviceModal() {
        document.getElementById('addDeviceModal').style.display = 'none';
        document.getElementById('addDeviceForm').reset();
    }

    function openEditDeviceModal(deviceId, name, deviceType, connectionType, ipAddress, apiEndpoint, apiUrl, port, serialNumber, location, syncInterval, autoSync) {
        const form = document.getElementById('editDeviceForm');
        form.action = `/dashboard/devices/${deviceId}`;

        document.getElementById('editDeviceName').value = name;
        document.getElementById('editDeviceType').value = deviceType;
        document.getElementById('editConnectionType').value = connectionType || 'adms';
        document.getElementById('editIpAddress').value = ipAddress;
        document.getElementById('editApiEndpoint').value = apiEndpoint || '';
        document.getElementById('editApiUrl').value = apiUrl || '';
        document.getElementById('editPort').value = port;
        document.getElementById('editApiPort').value = port;
        document.getElementById('editSerialNumber').value = serialNumber;
        document.getElementById('editLocation').value = location;
        document.getElementById('editSyncInterval').value = syncInterval;
        document.getElementById('editAutoSync').checked = autoSync;

        toggleEditConnectionFields();

        document.getElementById('editDeviceModal').style.display = 'flex';
        document.getElementById('editDeviceName').focus();
    }

    function closeEditDeviceModal() {
        document.getElementById('editDeviceModal').style.display = 'none';
        document.getElementById('editDeviceForm').reset();
        toggleEditConnectionFields();
    }

    function toggleAddConnectionFields() {
        const connectionType = document.getElementById('connectionType').value;
        const admsFields = document.getElementById('addAdmsFields');
        const apiFields = document.getElementById('addApiFields');
        const ipAddress = document.getElementById('ipAddress');
        const apiEndpoint = document.getElementById('apiEndpoint');
        const apiUrl = document.getElementById('apiUrl');
        const port = document.getElementById('port');
        const apiPort = document.getElementById('apiPort');

        if (connectionType === 'api') {
            admsFields.style.display = 'none';
            apiFields.style.display = 'block';
            ipAddress.required = false;
            apiEndpoint.required = true;
            apiUrl.required = true;
            port.value = apiPort.value || port.value;
        } else {
            admsFields.style.display = 'grid';
            apiFields.style.display = 'none';
            ipAddress.required = true;
            apiEndpoint.required = false;
            apiUrl.required = false;
            port.value = apiPort.value || port.value;
        }
    }

    function toggleEditConnectionFields() {
        const connectionType = document.getElementById('editConnectionType').value;
        const admsFields = document.getElementById('editAdmsFields');
        const apiFields = document.getElementById('editApiFields');
        const ipAddress = document.getElementById('editIpAddress');
        const apiEndpoint = document.getElementById('editApiEndpoint');
        const apiUrl = document.getElementById('editApiUrl');
        const port = document.getElementById('editPort');
        const apiPort = document.getElementById('editApiPort');

        if (connectionType === 'api') {
            admsFields.style.display = 'none';
            apiFields.style.display = 'block';
            ipAddress.required = false;
            apiEndpoint.required = true;
            apiUrl.required = true;
            port.value = apiPort.value || port.value;
        } else {
            admsFields.style.display = 'grid';
            apiFields.style.display = 'none';
            ipAddress.required = true;
            apiEndpoint.required = false;
            apiUrl.required = false;
            port.value = apiPort.value || port.value;
        }
    }

    function confirmDelete(deviceId, deviceName) {
        const label = deviceName || 'this device';
        showDanger('Delete Device', `Are you sure you want to delete ${label}? This action cannot be undone.`).then(function (confirmed) {
            if (!confirmed) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/dashboard/devices/${deviceId}`;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        });
    }

    function testDevice(deviceId, deviceName) {
        const label = deviceName || 'this device';
        showConfirm('Test Device Connection', `Run connection test for ${label}?`).then(function (confirmed) {
            if (!confirmed) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/dashboard/devices/${deviceId}/test`;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
            document.body.appendChild(form);
            form.submit();
        });
    }

    function syncDeviceUsers(button, deviceId, deviceName) {
        const label = deviceName || 'this device';
        showConfirm('Sync Device Users', `Fetch all users from ${label} and sync to employees?`).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            const labelNode = button.querySelector('span');
            const originalLabel = labelNode ? labelNode.textContent : 'Sync Now';

            button.disabled = true;
            button.style.opacity = '0.75';
            button.style.cursor = 'not-allowed';

            if (labelNode) {
                labelNode.textContent = 'Syncing...';
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/dashboard/devices/${deviceId}/sync-users`;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
            document.body.appendChild(form);
            form.submit();

            setTimeout(function () {
                button.disabled = false;
                button.style.opacity = '1';
                button.style.cursor = 'pointer';

                if (labelNode) {
                    labelNode.textContent = originalLabel;
                }
            }, 12000);
        });
    }

    function openImportProgressModal() {
        const modal = document.getElementById('importProgressModal');
        const log = document.getElementById('importProgressLog');
        const status = document.getElementById('importProgressStatus');
        const bar = document.getElementById('importProgressBar');
        const percent = document.getElementById('importProgressPercent');

        modal.style.display = 'flex';
        log.textContent = 'Fetching attendance data...';
        status.textContent = 'Fetching attendance data...';
        bar.style.width = '0%';
        percent.textContent = '0%';
    }

    function closeImportProgressModal() {
        document.getElementById('importProgressModal').style.display = 'none';
    }

    function appendImportLog(message) {
        const log = document.getElementById('importProgressLog');
        log.textContent += `\n${message}`;
        log.scrollTop = log.scrollHeight;
    }

    function updateImportProgress(payload) {
        const percentValue = Number(payload.percentage || 0);
        document.getElementById('importProgressStatus').textContent = payload.message || 'Processing...';
        document.getElementById('importProgressBar').style.width = `${percentValue}%`;
        document.getElementById('importProgressPercent').textContent = `${percentValue}%`;
    }

    async function importDeviceData(button, deviceId, deviceName) {
        const label = deviceName || 'this device';
        const confirmed = await showConfirm('Import Device Data', `Import attendance records from ${label}?`);

        if (!confirmed) {
            return;
        }

        button.disabled = true;
        button.style.opacity = '0.75';
        button.style.cursor = 'not-allowed';

        openImportProgressModal();

        try {
            const response = await fetch(`/dashboard/devices/${deviceId}/import-attendance`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'text/plain',
                },
            });

            if (!response.ok || !response.body) {
                appendImportLog('Import failed: unable to connect to import endpoint.');
                document.getElementById('importProgressStatus').textContent = 'Import failed.';
                return;
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    const trimmed = line.trim();
                    if (!trimmed) {
                        continue;
                    }

                    let payload;
                    try {
                        payload = JSON.parse(trimmed);
                    } catch (error) {
                        continue;
                    }

                    if (payload.type === 'status') {
                        appendImportLog(payload.message || 'Starting import...');
                    }

                    if (payload.type === 'progress') {
                        updateImportProgress(payload);
                        appendImportLog(`✔ ${payload.processed} / ${payload.total} (${payload.percentage}%)`);
                    }

                    if (payload.type === 'error') {
                        appendImportLog(`Error: ${payload.message || 'Unknown error'}`);
                        document.getElementById('importProgressStatus').textContent = 'Import failed.';
                    }

                    if (payload.type === 'complete') {
                        updateImportProgress({
                            percentage: 100,
                            message: 'Import completed successfully.',
                        });
                        appendImportLog('');
                        appendImportLog('Import Completed Successfully');
                        appendImportLog(`Inserted: ${payload.inserted}`);
                        appendImportLog(`Skipped (Duplicates): ${payload.skipped}`);
                        appendImportLog(`Failed: ${payload.failed}`);
                        appendImportLog(`Total Processed: ${payload.processed}`);
                    }
                }
            }
        } catch (error) {
            appendImportLog(`Import failed: ${error.message || 'Unknown error'}`);
            document.getElementById('importProgressStatus').textContent = 'Import failed.';
        } finally {
            button.disabled = false;
            button.style.opacity = '1';
            button.style.cursor = 'pointer';
        }
    }

    async function syncTodayData(button, deviceId, deviceName) {
        const label = deviceName || 'this device';
        const confirmed = await showConfirm('Sync Today', `Sync today's attendance records from ${label}?`);

        if (!confirmed) {
            return;
        }

        button.disabled = true;
        button.style.opacity = '0.75';
        button.style.cursor = 'not-allowed';

        openImportProgressModal();
        document.getElementById('importProgressStatus').textContent = 'Fetching today attendance data...';
        document.getElementById('importProgressLog').textContent = 'Fetching today attendance data...';

        try {
            const response = await fetch(`/dashboard/devices/${deviceId}/sync-today`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'text/plain',
                },
            });

            if (!response.ok || !response.body) {
                appendImportLog('Sync today failed: unable to connect to endpoint.');
                document.getElementById('importProgressStatus').textContent = 'Sync today failed.';
                return;
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    const trimmed = line.trim();
                    if (!trimmed) {
                        continue;
                    }

                    let payload;
                    try {
                        payload = JSON.parse(trimmed);
                    } catch (error) {
                        continue;
                    }

                    if (payload.type === 'status') {
                        appendImportLog(payload.message || 'Starting today sync...');
                    }

                    if (payload.type === 'progress') {
                        updateImportProgress(payload);
                        appendImportLog(`✔ ${payload.processed} / ${payload.total} (${payload.percentage}%)`);
                    }

                    if (payload.type === 'error') {
                        appendImportLog(`Error: ${payload.message || 'Unknown error'}`);
                        document.getElementById('importProgressStatus').textContent = 'Sync today failed.';
                    }

                    if (payload.type === 'complete') {
                        updateImportProgress({
                            percentage: 100,
                            message: 'Sync today completed successfully.',
                        });
                        appendImportLog('');
                        appendImportLog('Sync Today Completed Successfully');
                        appendImportLog(`Inserted: ${payload.inserted}`);
                        appendImportLog(`Skipped (Duplicates/Non-Today): ${payload.skipped}`);
                        appendImportLog(`Failed: ${payload.failed}`);
                        appendImportLog(`Total Processed: ${payload.processed}`);
                    }
                }
            }
        } catch (error) {
            appendImportLog(`Sync today failed: ${error.message || 'Unknown error'}`);
            document.getElementById('importProgressStatus').textContent = 'Sync today failed.';
        } finally {
            button.disabled = false;
            button.style.opacity = '1';
            button.style.cursor = 'pointer';
        }
    }

    document.getElementById('addDeviceModal').addEventListener('click', function (e) {
        if (e.target === this) closeAddDeviceModal();
    });

    document.getElementById('editDeviceModal').addEventListener('click', function (e) {
        if (e.target === this) closeEditDeviceModal();
    });

    document.getElementById('importProgressModal').addEventListener('click', function (e) {
        if (e.target === this) closeImportProgressModal();
    });

    document.getElementById('apiPort').addEventListener('input', function () {
        document.getElementById('port').value = this.value;
    });

    document.getElementById('editApiPort').addEventListener('input', function () {
        document.getElementById('editPort').value = this.value;
    });

    toggleAddConnectionFields();
    toggleEditConnectionFields();
</script>
@endpush

