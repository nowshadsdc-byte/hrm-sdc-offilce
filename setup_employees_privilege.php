<?php

// Quick setup script for employees.access privilege

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use HasinHayder\Tyro\Models\Privilege;
use HasinHayder\Tyro\Models\Role;
use HasinHayder\Tyro\Support\TyroCache;

// Step 1: Create or get privilege
$priv = Privilege::where('slug', 'employees.access')->first();
if (! $priv) {
    $priv = Privilege::create([
        'name' => 'Employees: Access',
        'slug' => 'employees.access',
        'description' => 'Access employees module',
    ]);
    echo "✓ Created privilege: employees.access\n";
} else {
    echo "✓ Privilege exists: employees.access\n";
}

// Step 2: Find Employee role
$role = Role::where('slug', 'employee')->first()
    ?: Role::where('name', 'Employee')->first();

if (! $role) {
    echo "✗ ERROR: Employee role not found\n";
    echo "Available roles:\n";
    foreach (Role::all() as $r) {
        echo "  - {$r->name} (slug: {$r->slug})\n";
    }
    exit(1);
}
echo "✓ Role found: {$role->name} (slug: {$role->slug})\n";

// Step 3: Attach privilege to role
if (! $role->hasPrivilege('employees.access')) {
    $role->attachPrivilege($priv);
    echo "✓ Attached privilege to role\n";
} else {
    echo "✓ Privilege already attached\n";
}

// Step 4: Clear cache for all users with employee role
TyroCache::forgetUsersByRole($role);
echo "✓ Cleared cache for users with employee role\n";

// Step 5: Verify
$slugs = $role->privileges()->pluck('slug')->all();
echo '✓ Role privileges: '.json_encode($slugs)."\n";

echo "\n✓ Setup complete! Employee role now has 'employees.access' privilege.\n";
