<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         //User::factory(10)->create();

         $user = User::factory()->create([
             'active' => true,
             'username' => 'Developer',
             'email' => 'dev@checkiitoto.co.tz',
             'verified_at' => now(),
             'employee_id' => 1,
             'password' => Hash::make('Qwerty@123'),
         ]);

         // Assign admin role to the user
         $adminRole = Role::where('name', 'admin')->first();
         if ($adminRole) {
             $user->roles()->attach($adminRole->id);
         }
    }
}
