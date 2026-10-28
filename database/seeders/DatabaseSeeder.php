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
            'name' => 'Eksper Danışmanlık',
            'type' => 'internal',
        ]);

        User::create([
            'id'         => Str::uuid(),
            'company_id' => $company->id,
            'name'       => 'Burak',
            'email'      => 'burak@eksper.com.tr',
            'role'       => 'super_admin',
            'password'   => Hash::make('password'),
        ]);

        User::create([
            'id'         => Str::uuid(),
            'company_id' => $company->id,
            'name'       => 'Can',
            'email'      => 'can@eksper.com.tr',
            'role'       => 'admin',
            'password'   => Hash::make('password'),
        ]);

        User::create([
            'id'         => Str::uuid(),
            'company_id' => $company->id,
            'name'       => 'Durul',
            'email'      => 'durul@eksper.com.tr',
            'role'       => 'member',
            'password'   => Hash::make('password'),
        ]);
    }
}
