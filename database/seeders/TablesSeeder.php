<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TablesSeeder extends Seeder{
    public function run()
    {

        // 4 - e9 96
        // 5 - e10 185
        // 6 - e7 180
        // 7 - e8 64
        // 8 - e4 66


        for ($i = 1; $i <= 66; $i++){
            DB::table('tables')->insertOrIgnore([
                'name' => ''. $i . ' стол',
                'code' => ''.$i . ' стол',
                'department_id' => 8,
                'worker_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
