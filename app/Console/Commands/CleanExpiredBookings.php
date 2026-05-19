<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanExpiredBookings extends Command
{
    protected $signature   = 'bookings:clean-expired';
    protected $description = 'Delete pending bookings whose 5-minute hold window has expired';

    public function handle(): int
    {
        $expiredIds = DB::table('bookings')
            ->where('booking_status', 'pending')
            ->where('expires_at', '<=', now())
            ->pluck('booking_id');

        if ($expiredIds->isEmpty()) {
            $this->info('No expired bookings found.');
            return Command::SUCCESS;
        }

        DB::table('tickets')->whereIn('booking_id', $expiredIds)->delete();
        DB::table('bookings')->whereIn('booking_id', $expiredIds)->delete();

        $this->info("Cleaned {$expiredIds->count()} expired booking(s).");
        return Command::SUCCESS;
    }
}