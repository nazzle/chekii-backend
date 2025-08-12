<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::factory()->create([
            'active' => true,
            'firstName' => 'Super',
            'lastName' => 'Administrator',
            'phone' => '+255688343017',
            'gender' => 'male',
            'address' => 'Dar es salaam',
        ]);
    }
}
