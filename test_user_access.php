<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

// Get user with Employee role (usually user id 2 or find by role)
$user = User::whereHas('roles', function ($q) {
    $q->where('slug', 'employee');
})->first();

if (! $user) {
    echo "No user found with Employee role\n";
    // Try user id 2
    $user = User::find(2);
    if (! $user) {
        echo "Could not find user 2\n";
        exit(1);
    }
    echo "Using user 2\n";
}

echo "User: {$user->name} (ID: {$user->id})\n";
echo 'Roles: '.json_encode($user->tyroRoleSlugs())."\n";
echo 'Privileges: '.json_encode($user->tyroPrivilegeSlugs())."\n";
echo "\nAccess checks:\n";
echo "  hasPrivilege('employees.access'): ".($user->hasPrivilege('employees.access') ? 'YES' : 'NO')."\n";
echo "  hasAnyRole(['admin','super-admin','employee']): ".($user->hasAnyRole(['admin', 'super-admin', 'employee']) ? 'YES' : 'NO')."\n";

if ($user->hasPrivilege('employees.access') || $user->hasAnyRole(['admin', 'super-admin', 'employee'])) {
    echo "\n✓ User SHOULD have access to /employees\n";
} else {
    echo "\n✗ User SHOULD NOT have access to /employees\n";
}
