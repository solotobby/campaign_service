<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ArielInfluencer;

class ArielInfluencerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $influencers = [
            [
                'full_name' => 'Dr. Folakemi Ezenwanne', 
                'code' => 'DRKEMI'
            ],
            [
                'full_name' => 'Wives and Mothers', 
                'code' => 'WIVES&MOTHERS'
            ],
            [
                'full_name' => 'Obis Ora', 
                'code' => 'OBIS'
            ],
            [
                'full_name' => 'Fab Mum', 
                'code' => 'FABMUM'
            ],
            [
                'full_name' => 'Imumzone', 
                'code' => 'IMUM'
            ],
            [
                'full_name' => 'Tito Bez- Idakula', 
                'code' => 'TITOBEZ'
            ],
            [
                'full_name' => 'Diary of a naija girl', 
                'code' => 'DANG'
            ],
            [
                'full_name' => 'Tomike', 
                'code' => 'TOMIKE'
            ],
            [
                'full_name' => 'Dimma Umeh', 
                'code' => 'DIMMA'
            ],
            [
                'full_name' => 'Healthertainer', 
                'code' => 'HEALTHERTAINER'
            ],
            [
                'full_name' => 'Matilda Obaseki', 
                'code' => 'MATILDA'
            ],
            [
                'full_name' => 'Chisom Onwuegbuzia', 
                'code' => 'CHISOM'
            ],
            [
                'full_name' => 'Zic Saloma', 
                'code' => 'ZICSALOMA'
            ],
            [
                'full_name' => 'Aunty Adaa', 
                'code' => 'AUNTYADAA'
            ],
            [
                'full_name' => 'Thisthingcalledfashion', 
                'code' => 'NONYE'
            ],
            [
                'full_name' => 'ArielNigeria', 
                'code' => 'ARIELNG'
            ],
            [
                'full_name' => 'DR KEMI', 
                'code' => 'DR KEMI'
            ],
            [
                'full_name' => 'FAB MUM', 
                'code' => 'FAB MUM'
            ],
            [
                'full_name' => 'ZIC SALOMA', 
                'code' => 'ZIC SALOMA'
            ],
            [
                'full_name' => 'AUNTY ADAA', 
                'code' => 'AUNTY ADAA'
            ],
            [
                'full_name' => 'WIVES AND MOTHERS', 
                'code' => 'WIVESANDMOTHERS'
            ],
            [
                'full_name' => 'WIVES AND MOTHERS', 
                'code' => 'WIVES & MOTHERS'
            ],
            [
                'full_name' => 'WIVES AND MOTHERS', 
                'code' => 'WIVES AND MOTHERS'
            ]
        ];

        // Update DRFOLA INFLUENCER
        $drFola = ArielInfluencer::where('code', 'drfola')->first();
        if ($drFola) {
            $drFola->code = 'drkemi';
            $drFola->save();
        }

        foreach ($influencers as $influencer) {
            ArielInfluencer::updateOrCreate(['code' => strtolower($influencer['code'])], [
                'full_name' => $influencer['full_name']
            ]);
        }
    }
}