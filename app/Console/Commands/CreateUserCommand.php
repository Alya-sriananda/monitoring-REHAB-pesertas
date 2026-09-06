<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Signature('app:create-user')]
#[Description('Create a new internal user (Admin/Petugas)')]
class CreateUserCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Nama Lengkap');
        $npp = $this->ask('Nomor Pokok Pegawai (NPP)');
        $email = $this->ask('Email BPJS Kesehatan');
        $role = $this->choice('Role', ['petugas', 'admin'], 0);

        if (User::where('npp', $npp)->exists()) {
            $this->error("User dengan NPP {$npp} sudah terdaftar!");

            return Command::FAILURE;
        }

        // Generate 8-character password: 2 Upper, 2 Lower, 3 Digits, 1 Symbol
        $password = Str::upper(Str::random(2)).Str::lower(Str::random(2)).rand(100, 999).'!';

        $user = User::create([
            'name' => $name,
            'npp' => $npp,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'aktif' => true,
            'must_change_password' => true,
        ]);

        $this->info('User berhasil dibuat!');
        $this->table(
            ['Nama', 'NPP', 'Email', 'Role', 'Password Sementara'],
            [[$user->name, $user->npp, $user->email, $user->role, $password]]
        );
        $this->warn('Silakan berikan NPP dan Password Sementara ini kepada user.');

        return Command::SUCCESS;
    }
}
