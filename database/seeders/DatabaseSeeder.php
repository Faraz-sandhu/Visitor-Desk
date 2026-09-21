<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@example.com'], ['role' => 'admin', 'name' => 'Front Desk Admin', 'password' => 'password']);
        $company = Company::firstOrCreate(['name' => 'Head Office'], ['email' => 'info@example.com', 'is_active' => true]);
        Employee::firstOrCreate(['email' => 'manager@example.com'], ['company_id' => $company->id, 'name' => 'Office Manager', 'designation' => 'Manager', 'is_active' => true]);
    }
}
