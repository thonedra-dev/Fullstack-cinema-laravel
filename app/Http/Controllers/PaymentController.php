<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Stripe secret key set once per request lifecycle
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /* ══════════════════════════════════════════════════════════
       GET /booking/payment
       Shows payment summary + Stripe card element.
    ══════════════════════════════════════════════════════════ */
    public function show()
    {
        if (! auth('customer')->check()) {
            return redirect()->route('users.login');
        }

        $bookingId = session('current_booking_id');
        if (! $bookingId) {
            return redirect()->route('home');
        }

        $booking = Booking::with([
            'tickets.seat',
            'tickets.showtime',
            'tickets.movieTicketPrice',
        ])->find($bookingId);

        if (! $booking || (int) $booking->user_id !== (int) auth('customer')->id()) {
            return redirect()->route('home');
        }

        // If already paid, skip straight to confirmation
        if ($booking->booking_status === 'confirmed') {
            return redirect()->route('booking.confirmed', $booking->booking_id);
        }

        $firstTicket = $booking->tickets->first();
        $showtime    = $firstTicket?->showtime;
        $movie       = $showtime ? Movie::find($showtime->movie_id) : null;
        $hall        = $showtime ? Hall::find($showtime->hall_id) : null;
        $theatre     = $hall
            ? DB::table('theatres')->where('theatre_id', $hall->theatre_id)->first()
            : null;
        $cinema = DB::table('cinemas')->where('cinema_id', $booking->cinema_id)->first();

        $customer  = auth('customer')->user();
        $expiresAt = $booking->expires_at?->toIso8601String();

return view('users.payment', [
    'booking'   => $booking,
    'movie'     => $movie,
    'cinema'    => $cinema,
    'showtime'  => $showtime,
    'theatre'   => $theatre,
    'customer'  => $customer,
    'expiresAt' => $expiresAt,
    'stripeKey' => config('services.stripe.key'),
]);
    }

    /* ══════════════════════════════════════════════════════════
       POST /booking/payment/intent
       Creates (or reuses) a Stripe PaymentIntent.
       Returns { clientSecret }.
    ══════════════════════════════════════════════════════════ */
    public function createIntent(Request $request)
    {
        if (! auth('customer')->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $bookingId = session('current_booking_id');
        $booking   = Booking::find($bookingId);

        if (! $booking || (int) $booking->user_id !== (int) auth('customer')->id()) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        if ($booking->booking_status === 'confirmed') {
            return response()->json(['error' => 'Already paid'], 400);
        }

        // Reuse existing intent if one was already created
        if ($booking->stripe_payment_intent_id) {
            try {
                $existing = PaymentIntent::retrieve($booking->stripe_payment_intent_id);
                if ($existing->status !== 'canceled') {
                    return response()->json(['clientSecret' => $existing->client_secret]);
                }
            } catch (\Exception $e) {
                // Intent not found on Stripe side — create a fresh one below
            }
        }

        // Amount in smallest currency unit (cents for USD, sen for MYR, etc.)
        // Change 'usd' to your currency code if needed (e.g. 'myr')
        $amountCents = (int) round((float) $booking->total_amount * 100);

        $intent = PaymentIntent::create([
            'amount'   => $amountCents,
            'currency' => 'usd',
            'metadata' => [
                'booking_id' => $booking->booking_id,
                'user_id'    => $booking->user_id,
            ],
        ]);

        $booking->update(['stripe_payment_intent_id' => $intent->id]);

        return response()->json(['clientSecret' => $intent->client_secret]);
    }

    /* ══════════════════════════════════════════════════════════
       POST /booking/payment/confirm
       Called by client JS after stripe.confirmCardPayment succeeds.
       Verifies status with Stripe, then finalises booking + payment.
    ══════════════════════════════════════════════════════════ */
    public function confirm(Request $request)
    {
        if (! auth('customer')->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate(['payment_intent_id' => 'required|string']);

        $bookingId = session('current_booking_id');
        $booking   = Booking::find($bookingId);

        if (! $booking || (int) $booking->user_id !== (int) auth('customer')->id()) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        // Double-check with Stripe (never trust the client alone)
        try {
            $intent = PaymentIntent::retrieve($request->payment_intent_id);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not verify payment with Stripe.'], 500);
        }

        if ($intent->status !== 'succeeded') {
            return response()->json(['error' => 'Payment not confirmed by Stripe.'], 400);
        }

        // Idempotent: only update if not already confirmed
        if ($booking->booking_status !== 'confirmed') {
            $booking->update([
                'booking_status'           => 'confirmed',
                'stripe_payment_intent_id' => $intent->id,
            ]);

            // Insert payments record (nullable Stripe columns now filled)
            DB::table('payments')->insert([
                'booking_id'       => $booking->booking_id,
                'amount_paid'      => $booking->total_amount,
                'stripe_intent_id' => $intent->id,
                'payment_status'   => 'paid',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        session()->forget(['current_booking_id', 'seat_selection_url']);

        return response()->json([
            'success'    => true,
            'booking_id' => $booking->booking_id,
            'redirect'   => route('booking.confirmed', $booking->booking_id),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
       POST /booking/stripe/webhook
       Stripe server-to-server fallback. Exempt from CSRF in
       App\Http\Middleware\VerifyCsrfToken::$except.
    ══════════════════════════════════════════════════════════ */
    public function webhook(Request $request)
    {
        $payload      = $request->getContent();
        $sigHeader    = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $intent    = $event->data->object;
            $bookingId = $intent->metadata->booking_id ?? null;

            if ($bookingId) {
                $booking = Booking::find($bookingId);

                if ($booking && $booking->booking_status !== 'confirmed') {
                    $booking->update([
                        'booking_status'           => 'confirmed',
                        'stripe_payment_intent_id' => $intent->id,
                    ]);

                    $alreadyLogged = DB::table('payments')
                        ->where('booking_id', $bookingId)
                        ->exists();

                    if (! $alreadyLogged) {
                        DB::table('payments')->insert([
                            'booking_id'       => $bookingId,
                            'amount_paid'      => $booking->total_amount,
                            'stripe_intent_id' => $intent->id,
                            'payment_status'   => 'paid',
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);
                    }
                }
            }
        }

        return response('OK', 200);
    }

    /* ══════════════════════════════════════════════════════════
       GET /booking/confirmed/{bookingId}
    ══════════════════════════════════════════════════════════ */
    public function confirmed(int $bookingId)
    {
        if (! auth('customer')->check()) {
            return redirect()->route('users.login');
        }

        $booking = Booking::with([
            'tickets.seat',
            'tickets.showtime',
        ])->find($bookingId);

        if (! $booking || (int) $booking->user_id !== (int) auth('customer')->id()) {
            return redirect()->route('home');
        }

        $firstTicket = $booking->tickets->first();
        $showtime    = $firstTicket?->showtime;
        $movie       = $showtime ? Movie::find($showtime->movie_id) : null;
        $cinema      = DB::table('cinemas')->where('cinema_id', $booking->cinema_id)->first();

        return view('users.booking_confirmed', compact(
            'booking', 'movie', 'cinema', 'showtime'
        ));
    }
}