<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::create([
            'id'   => Str::uuid(),
            'name' => 'M2H',
            'type' => 'internal',
        ]);

        User::create([
            'id'         => Str::uuid(),
            'company_id' => $company->id,
            'name'       => 'Burak',
            'email'      => 'burak@m2h.ge',
            'role'       => 'super_admin',
            'password'   => Hash::make('password'),
        ]);

        User::create([
            'id'         => Str::uuid(),
            'company_id' => $company->id,
            'name'       => 'Can',
            'email'      => 'can@m2h.ge',
            'role'       => 'admin',
            'password'   => Hash::make('password'),
        ]);

        User::create([
            'id'         => Str::uuid(),
            'company_id' => $company->id,
            'name'       => 'Durul',
            'email'      => 'durul@m2h.ge',
            'role'       => 'member',
            'password'   => Hash::make('password'),
        ]);
    }
}
