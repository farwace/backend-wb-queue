<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TablesSeeder extends Seeder{
    public function run()
    {
        for ($i = 1; $i <= 48; $i++){
            DB::table('squares')->insertOrIgnore([
                'name' => ''. $i . ' квадрат',
                'code' => ''.$i . ' квадрат',
                'department_id' => 7,
                'max_workers' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
