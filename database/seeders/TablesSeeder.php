<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TablesSeeder extends Seeder{
    public function run()
    {
        for ($i = 1; $i <= 100; $i++){
            DB::table('tables')->insertOrIgnore([
                'name' => ''. $i . ' стол',
                'code' => ''.$i . ' стол',
                'department_id' => 3,
                'worker_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
