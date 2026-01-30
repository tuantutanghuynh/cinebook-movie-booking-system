<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\ShowtimePrice;
use App\Models\ShowtimeSeat;
use App\Models\Movie;
use App\Models\Room;
use App\Models\SeatType;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AdminShowtimeController
 * 
 * Handles admin showtime management including:
 * - Showtime listing and filtering
 * - Showtime creation and editing
 * - Price and seat configuration
 * - Schedule management and validation
 */
class AdminShowtimeController extends Controller
{
    public function index(Request $request)
    {
        $query = Showtime::with(['movie', 'room.screenType', 'room.seats', 'showtimeSeats']);

        // Filter by movie
        if ($request->movie_id) {
            $query->where('movie_id', $request->movie_id);
        }

        // Filter by room
        if ($request->room_id) {
            $query->where('room_id', $request->room_id);
        }

        // Filter by date
        if ($request->date) {
            $query->whereDate('show_date', $request->date);
        }

        // Filter empty showtimes (no bookings) - only future showtimes
        if ($request->filter === 'empty') {
            $now = Carbon::now();
            $query->whereDoesntHave('showtimeSeats', function ($q) {
                $q->where('status', 'booked');
            })
            // Only include upcoming showtimes (not ended)
            ->whereRaw("CONCAT(show_date, ' ', show_time) > ?", [$now->format('Y-m-d H:i:s')]);
        }

        // Sort order based on filter
        if ($request->filter === 'empty') {
            // Empty showtimes: sort by date ASC (earliest first - most urgent)
            $showtimes = $query->orderBy('show_date', 'asc')
                ->orderBy('show_time', 'asc')
                ->paginate(20);
        } else {
            $showtimes = $query->orderBy('show_date', 'desc')
                ->orderBy('show_time', 'desc')
                ->paginate(20);
        }

        // Ensure all showtimes have showtime seats created
        foreach ($showtimes as $showtime) {
            $this->ensureShowtimeSeats($showtime);
        }

        $movies = Movie::where('status', '!=', 'ended')->orderBy('title')->get();
        $rooms = Room::with('screenType')->orderBy('name')->get();

        return view('admin.showtimes.index', compact('showtimes', 'movies', 'rooms'));
    }

    /**
     * Ensure showtime has all seats created
     */
    private function ensureShowtimeSeats(Showtime $showtime)
    {
        $room = $showtime->room;
        $existingSeats = $showtime->showtimeSeats->pluck('seat_id')->toArray();
        
        foreach ($room->seats as $seat) {
            if (!in_array($seat->id, $existingSeats)) {
                ShowtimeSeat::create([
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'status' => 'available',
                ]);
            }
        }
    }

    public function create()
    {
        $movies = Movie::where('status', 'now_showing')->orderBy('title')->get();
        $rooms = Room::with('screenType')->orderBy('name')->get();
        $seatTypes = SeatType::all();

        return view('admin.showtimes.create', compact('movies', 'rooms', 'seatTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'room_id' => 'required|exists:rooms,id',
            'show_date' => 'required|date',
            'show_time' => 'required',
            'seat_type_prices' => 'required|array',
            'seat_type_prices.*' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            // Get movie duration to calculate end time
            $movie = Movie::findOrFail($request->movie_id);

            // Calculate start and end datetime for the new showtime
            $startDatetime = Carbon::parse($request->show_date . ' ' . $request->show_time);
            $endDatetime = $startDatetime->copy()->addMinutes($movie->duration);

            // Check for overlapping showtimes in the same room
            if (Showtime::hasOverlap($request->room_id, $startDatetime, $endDatetime)) {
                $overlapping = Showtime::getOverlappingShowtimes($request->room_id, $startDatetime, $endDatetime);
                $conflictInfo = $overlapping->map(function (\App\Models\Showtime $s) {
                    return $s->movie->title . ' (' . $s->start_datetime->format('H:i') . ' - ' . $s->end_datetime->format('H:i') . ')';
                })->join(', ');

                return back()->with('error', 'Showtime overlaps with existing schedule: ' . $conflictInfo);
            }

            // Create showtime
            $showtime = Showtime::create([
                'movie_id' => $request->movie_id,
                'room_id' => $request->room_id,
                'show_date' => $request->show_date,
                'show_time' => $request->show_time,
            ]);

            // Create showtime prices for each seat type
            foreach ($request->seat_type_prices as $seatTypeId => $price) {
                ShowtimePrice::create([
                    'showtime_id' => $showtime->id,
                    'seat_type_id' => $seatTypeId,
                    'price' => $price,
                ]);
            }

            // Get all seats in the room
            $room = Room::with('seats')->find($request->room_id);
            
            // Create showtime_seats for each seat
            foreach ($room->seats as $seat) {
                ShowtimeSeat::create([
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'status' => 'available',
                ]);
            }

            DB::commit();
            return redirect()->route('admin.showtimes.index')
                ->with('success', 'Showtime created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create showtime: ' . $e->getMessage());
        }
    }

    public function edit(Showtime $showtime)
    {
        $showtime->load(['movie', 'room.screenType', 'showtimePrices.seatType', 'showtimeSeats']);
        
        // Always restrict to pricing only
        $hasBookings = true; // Force restriction mode
        
        $movies = Movie::where('status', 'now_showing')->orderBy('title')->get();
        $rooms = Room::with('screenType')->orderBy('name')->get();
        $seatTypes = SeatType::all();

        return view('admin.showtimes.edit', compact('showtime', 'movies', 'rooms', 'seatTypes', 'hasBookings'));
    }

    public function update(Request $request, Showtime $showtime)
    {
        // Only allow pricing updates - no other fields can be modified
        $request->validate([
            'seat_type_prices' => 'required|array',
            'seat_type_prices.*' => 'required|numeric|min:0'
        ]);
        
        DB::beginTransaction();
        try {
            // Update showtime prices only
            foreach ($request->seat_type_prices as $seatTypeId => $price) {
                ShowtimePrice::updateOrCreate(
                    [
                        'showtime_id' => $showtime->id,
                        'seat_type_id' => $seatTypeId,
                    ],
                    ['price' => $price]
                );
            }
            
            DB::commit();
            return redirect()->route('admin.showtimes.index')
                ->with('success', 'Peak hour pricing updated successfully');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update pricing: ' . $e->getMessage());
        }
    }

    public function destroy(Showtime $showtime)
    {
        DB::beginTransaction();
        try {
            // Check if there are any confirmed bookings
            if ($showtime->bookings()->whereIn('status', ['confirmed', 'pending'])->exists()) {
                return back()->with('error', 'Cannot delete showtime - there are existing customer bookings. Use "Cancel Showtime" instead to process refunds.');
            }

            // Check if there are any seats that are booked or reserved
            $hasBookedSeats = $showtime->showtimeSeats()
                ->whereIn('status', ['booked', 'reserved'])
                ->exists();
                
            if ($hasBookedSeats) {
                return back()->with('error', 'Cannot delete showtime - there are booked or reserved seats');
            }

            // Delete showtime seats (only available ones should remain at this point)
            $showtime->showtimeSeats()->delete();

            // Delete showtime prices
            $showtime->showtimePrices()->delete();

            // Delete showtime
            $showtime->delete();

            DB::commit();
            return back()->with('success', 'Showtime deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete showtime: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a showtime and process all affected bookings
     * - Cancels all bookings (confirmed and pending)
     * - Issues refunds for paid bookings
     * - Releases all seats
     * - Cancels all QR codes
     * - Sends email notifications to all affected customers
     */
    public function cancelShowtime(Request $request, Showtime $showtime)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $reason = $request->input('reason', 'Technical issue - showtime cancelled by cinema');
            
            // Get all bookings for this showtime (confirmed and pending)
            $bookings = $showtime->bookings()
                ->with(['user', 'bookingSeats.seat'])
                ->whereIn('status', ['confirmed', 'pending'])
                ->get();

            if ($bookings->isEmpty()) {
                return back()->with('error', 'No active bookings found for this showtime');
            }

            $cancelledCount = 0;
            $refundTotal = 0;
            $emailsSent = 0;

            foreach ($bookings as $booking) {
                // Calculate refund amount (full refund for paid bookings)
                // NOTE: This is for notification only - no actual payment gateway integration yet
                // In production: call Momo/VNPay refund API here
                $refundAmount = ($booking->payment_status === 'paid') ? $booking->total_price : 0;
                $refundTotal += $refundAmount;

                // Update booking status to cancelled
                // Keep payment_status as 'paid' for audit trail
                // In production: update to 'refunded' only after successful API call
                $booking->update([
                    'status' => 'cancelled',
                ]);

                // Cancel all QR codes for this booking
                DB::table('booking_seats')
                    ->where('booking_id', $booking->id)
                    ->update(['qr_status' => 'cancelled']);

                // Release all seats for this booking
                foreach ($booking->bookingSeats as $bookingSeat) {
                    DB::table('showtime_seats')
                        ->where('showtime_id', $showtime->id)
                        ->where('seat_id', $bookingSeat->seat_id)
                        ->update(['status' => 'available']);
                }

                // Send cancellation email to customer
                try {
                    if ($booking->user && $booking->user->email) {
                        \Mail::to($booking->user->email)->send(
                            new \App\Mail\ShowtimeCancellationMail($showtime, $booking, $reason, $refundAmount)
                        );
                        $emailsSent++;
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to send showtime cancellation email for booking #{$booking->id}: " . $e->getMessage());
                }

                $cancelledCount++;
            }

            DB::commit();

            $message = "Showtime cancelled successfully! ";
            $message .= "Cancelled {$cancelledCount} booking(s). ";
            if ($refundTotal > 0) {
                $message .= "Total refunds to process MANUALLY: " . number_format($refundTotal, 0) . " VND. ";
                $message .= "⚠️ Please process refunds manually via payment gateway. ";
            }
            $message .= "Notification emails sent to {$emailsSent} customer(s).";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to cancel showtime: ' . $e->getMessage());
        }
    }
}