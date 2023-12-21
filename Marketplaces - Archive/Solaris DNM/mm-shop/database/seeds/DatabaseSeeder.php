<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(CitiesTableSeeder::class);
        // $this->call(CategoriesTableSeeder::class);
        $this->call(RegionTableSeeder::class);
    }
}
