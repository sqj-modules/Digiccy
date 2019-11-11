<?php
namespace SQJ\Modules\Digiccy\Database\Seeds;

use Illuminate\Database\Seeder;

class DigiccyDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         $this->call([
             DigiccyCurrencySeeder::class,
             DigiccyContractSeeder::class
         ]);
    }
}
