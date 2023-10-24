<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ArielShoppingSite;

class ArielShoppingSitesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sites = [
            [
                'site' => 'jumia', 
                'icon' => 'https://getcake.com/wp-content/uploads/2017/03/Jumia-logo.png', 
                'url' => 'https://www.jumia.com.ng/mlp-p-g-store/ariel/'
            ],
            [
                'site' => 'konga', 
                'icon' => 'https://www.realwire.com/writeitfiles/konga_logo.jpg', 
                'url' => 'https://www.konga.com/merchant/170029?page=1&brand=Ariel'
            ]
        ];

        foreach ($sites as $site) {
            ArielShoppingSite::updateOrCreate(['name' => $site['site']], [
                'icon' => $site['icon'], 
                'url' => $site['url']
            ]);
        }
    }
}
