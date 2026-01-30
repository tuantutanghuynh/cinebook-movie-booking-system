<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ShowtimeSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingCancellationMail;
use Carbon\Carbon;

/**
 * AdminBookingController
 * 
 * Handles admin booking management operations including:
 * - Booking listing with filtering and search
 * - Booking detail view with relationships
 * - Booking cancellation and seat release
 * - Booking statistics and analytics
 */
class AdminBookingController extends Controller
{
    // Booking List Page
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'showtime.movie', 'showtime.room', 'bookingSeats.seat']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date
        if ($request->date) {
            $query->whereDate('booking_date', $request->date);
        }

        // Filter by showtime
        if ($request->showtime_id) {
            $query->where('showtime_id', $request->showtime_id);
        }

        // Search by user email or booking ID
        if ($request->search) {
            $search = $request->search;
            // Remove # prefix if searching by booking ID (e.g., "#17" -> "17")
            $searchId = preg_replace('/^#/', '', $search);
            $query->where(function($q) use ($search, $searchId) {
                $q->where('bookings.id', $searchId)
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('email', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort by showtime date and time, then by created_at
        $bookings = $query->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
                         ->orderBy('showtimes.show_date', 'desc')
                         ->orderBy('showtimes.show_time', 'desc')
                         ->orderBy('bookings.created_at', 'desc')
                         ->select('bookings.*')
                         ->paginate(20);

        // Get showtimes for filter dropdown (only future and recent showtimes)
        $showtimes = \App\Models\Showtime::with(['movie', 'room'])
                    ->where('show_date', '>=', now()->subDays(7))
                    ->orderBy('show_date', 'desc')
                    ->orderBy('show_time', 'desc')
                    ->get();

        // Statistics
        $today = Carbon::today();
        $stats = [
            'total' => Booking::count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'expired' => Booking::where('status', 'expired')->count(),
            'total_revenue' => Booking::where('payment_status', 'paid')->sum('total_price'),
            'today_bookings' => Booking::whereDate('created_at', $today)->count(),
            'cancelled_today' => Booking::whereDate('updated_at', $today)->where('status', 'cancelled')->count(),
        ];

        return view('admin.bookings.index', compact('bookings', 'showtimes', 'stats'));
    }

    // Booking Detail Page
    public function show(Booking $booking)
    {
        $booking->load([
            'user',
            'showtime.movie',
            'showtime.room.screenType',
            'bookingSeats.seat.seatType'
        ]);

        return view('admin.bookings.show', compact('booking'));
    }

    // Cancel Booking + Release Seats
    public function cancel(Request $request, Booking $booking)
    {
        if ($booking->status === 'cancelled' || $booking->status === 'expired') {
            return back()->with('error', 'Booking is already cancelled or expired');
        }

        // Check if showtime has ended - cannot cancel booking for ended showtimes
        $booking->load('showtime.movie');
        if ($booking->showtime->status === 'done') {
            return back()->with('error', 'Cannot cancel booking - the showtime has already ended');
        }

        DB::beginTransaction();
        try {
            // Get cancellation reason and refund amount from request
            $reason = $request->input('reason', 'Cancelled by administrator');
            // Calculate refund amount for notification only (no actual payment API integration)
            // TODO: Integrate Momo/VNPay refund API in production
            $refundAmount = $booking->payment_status === 'paid' ? $booking->total_price : 0;

            // Update booking status
            $booking->update(['status' => 'cancelled']);

            // Cancel all QR codes for this booking
            DB::table('booking_seats')
                ->where('booking_id', $booking->id)
                ->update(['qr_status' => 'cancelled']);

            // Release seats
            foreach ($booking->bookingSeats as $bookingSeat) {
                DB::table('showtime_seats')
                    ->where('showtime_id', $booking->showtime_id)
                    ->where('seat_id', $bookingSeat->seat_id)
                    ->update(['status' => 'available']);
            }

            DB::commit();

            // Send cancellation email
            try {
                $booking->load(['user', 'showtime.movie', 'showtime.room', 'bookingSeats.seat']);
                Mail::to($booking->user->email)->send(new BookingCancellationMail($booking, $reason, $refundAmount));
            } catch (\Exception $e) {
                \Log::error('Failed to send cancellation email: ' . $e->getMessage());
            }

            return back()->with('success', 'Booking cancelled successfully! Customer has been notified via email');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to cancel booking: ' . $e->getMessage());
        }
    }
}