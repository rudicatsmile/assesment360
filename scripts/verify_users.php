<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$u = App\Models\User::where('email', 'haikal.shodri@yalwash9.sch.id')->first();

if ($u) {
    echo "Name: " . $u->name . "\n";
    echo "Phone: " . $u->phone_number . "\n";
    echo "Dept: " . $u->department . "\n";
    echo "DeptID: " . $u->department_id . "\n";
    echo "Role: " . $u->role . "\n";
    echo "RoleID: " . $u->role_id . "\n";
    echo "Slug: " . $u->slug . "\n";
    echo "Prosentase: " . $u->prosentase . "\n";
    echo "Active: " . ($u->is_active ? 'yes' : 'no') . "\n";
    echo "Email: " . $u->email . "\n";
} else {
    echo "User not found\n";
}

echo "\nTotal users: " . App\Models\User::count() . "\n";
echo "Total departments: " . App\Models\Departement::count() . "\n";
