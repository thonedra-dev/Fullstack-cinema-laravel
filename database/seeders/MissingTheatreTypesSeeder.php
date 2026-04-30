<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MissingTheatreTypesSeeder extends Seeder
{
    public function run(): void
    {
        $cinemaIds = [5, 6];
        $theatreTypes = ['Standard', 'Deluxe', '3D Hall', 'VIP lounge', 'IMAX'];

        $assets = [
            'Standard' => [
                'icon' => '1774878634_icon_IMAX.png',
                'poster' => '1774878634_poster_IMAX.png',
            ],
            'Deluxe' => [
                'icon' => '1774879676_icon_PDF.png',
                'poster' => '1774879676_poster_deluxe.jpg',
            ],
            '3D Hall' => [
                'icon' => '1774879676_icon_PDF.png',
                'poster' => '1774879676_poster_deluxe.jpg',
            ],
            'VIP lounge' => [
                'icon' => '1774878634_icon_IMAX.png',
                'poster' => '1774878634_poster_IMAX.png',
            ],
            'IMAX' => [
                'icon' => '1774878634_icon_IMAX.png',
                'poster' => '1774878634_poster_IMAX.png',
            ],
        ];

        $inserted = 0;

        foreach ($cinemaIds as $cinemaId) {
            $cinemaExists = DB::table('cinemas')
                ->where('cinema_id', $cinemaId)
                ->exists();

            if (!$cinemaExists) {
                $this->command?->warn("Cinema #{$cinemaId} does not exist. Skipping.");
                continue;
            }

            foreach ($theatreTypes as $theatreName) {
                $exists = DB::table('theatres')
                    ->where('cinema_id', $cinemaId)
                    ->whereRaw('LOWER(theatre_name) = ?', [strtolower($theatreName)])
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('theatres')->insert([
                    'theatre_name' => $theatreName,
                    'theatre_icon' => $assets[$theatreName]['icon'],
                    'theatre_poster' => $assets[$theatreName]['poster'],
                    'cinema_id' => $cinemaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $inserted++;
            }
        }

        $this->command?->info("Inserted {$inserted} missing theatre type(s).");
    }
}
