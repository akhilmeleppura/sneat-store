<?php

namespace Modules\Cart\Database\Seeders;

use Illuminate\Database\Seeder;

class CartDatabaseSeeder extends Seeder
{
    /**
     * Run the Cart module database seeds.
     */
    public function run(): void
    {
        // Active shopping carts are transient and created dynamically by guest sessions or users.
        $this->command->info('Cart: Module initialized (transient session/database cart storage ready).');
    }
}
