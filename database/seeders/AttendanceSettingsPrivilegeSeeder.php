<?php

namespace Database\Seeders;

use HasinHayder\Tyro\Models\Privilege;
use Illuminate\Database\Seeder;

class AttendanceSettingsPrivilegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Privilege::query()->updateOrCreate(
            ['slug' => 'attendance-settings.access'],
            [
                'name' => 'Attendance Settings: Access',
                'description' => 'Access the attendance settings dashboard page.',
            ],
        );
    }
}
