<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Solok',
            'email' => 'admin@bpjs-kesehatan.go.id',
            'npp' => '123456',
            'role' => 'admin',
            'aktif' => true,
            'must_change_password' => false,
            'password' => Hash::make('PasswordAwal123!'),
        ]);
    }
}
