<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'username' => 'superadmin_ysb',
            'email'    => 'admin@syiarbangsa.org',
            'password' => Hash::make('ysbdev321'),
            'level'    => 'developer'
        ]);
    }
}
