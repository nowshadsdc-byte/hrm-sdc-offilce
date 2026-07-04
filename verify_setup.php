<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use HasinHayder\Tyro\Models\Privilege;
use HasinHayder\Tyro\Models\Role;

echo "\n========== SETUP VERIFICATION ==========\n\n";

// 1. Check privilege
echo "1. Privilege 'employees.access':\n";
$priv = Privilege::where('slug', 'employees.access')->first();
if ($priv) {
    echo "   ✓ EXISTS\n";
    echo "     - Name: {$priv->name}\n";
    echo "     - Slug: {$priv->slug}\n";
    echo "     - Description: {$priv->description}\n";
} else {
    echo "   ✗ MISSING\n";
    exit(1);
}

// 2. Check Employee role
echo "\n2. Role 'employee':\n";
$role = Role::where('slug', 'employee')->first();
if ($role) {
    echo "   ✓ EXISTS\n";
    echo "     - Name: {$role->name}\n";
    echo "     - Slug: {$role->slug}\n";
} else {
    echo "   ✗ MISSING\n";
    exit(1);
}

// 3. Check privilege attached to role
echo "\n3. Privilege attached to Employee role:\n";
if ($role->hasPrivilege('employees.access')) {
    echo "   ✓ YES - Privilege is attached\n";
    $rolePrivs = $role->privileges()->pluck('slug')->all();
    echo '     Role privileges: '.json_encode($rolePrivs)."\n";
} else {
    echo "   ✗ NO - Privilege not attached\n";
    exit(1);
}

// 4. Check users with Employee role
echo "\n4. Users with 'employee' role:\n";
$usersWithRole = User::whereHas('roles', function ($q) {
    $q->where('slug', 'employee');
})->get();

if ($usersWithRole->count() > 0) {
    echo '   ✓ FOUND '.$usersWithRole->count()." user(s)\n";
    foreach ($usersWithRole as $user) {
        echo "     - {$user->name} (ID: {$user->id}, email: {$user->email})\n";
        echo '       Roles: '.json_encode($user->tyroRoleSlugs())."\n";
        echo '       Privileges: '.json_encode($user->tyroPrivilegeSlugs())."\n";
    }
} else {
    echo "   ✗ No users with employee role\n";
}

echo "\n========== SETUP COMPLETE ==========\n";
echo "\n✓ The 'employees.access' privilege is configured for the 'employee' role.\n";
echo "✓ Users with the 'employee' role will have access to /employees\n";
echo "\nTo test:\n";
echo "  1. Log in with a user that has the 'employee' role\n";
echo "  2. Visit http://localhost:8000/employees\n";
echo "  3. You should see the employees list (no 403 error)\n";
