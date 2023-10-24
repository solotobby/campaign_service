<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $games = [
            [
                'name' => 'basketball', 
                'icon' => ''
            ],
            [
                'name' => 'spin the wheel', 
                'icon' => ''
            ],
        ];

        foreach ($games as $game) {
            Game::updateOrCreate(['name' => $game['name']]);
        }
    }
}