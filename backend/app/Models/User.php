<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Booking;
use App\Models\Review;


/**
 * User Model
 *
 * Represents a user in the cinema booking system.
 * Handles user authentication, profile management, and booking history.
 * Supports multiple roles: admin, user, guest.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'city',
        'phone',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get all bookings for this user
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get all reviews for this user
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // ==================== ROLE CHECKERS ====================

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isGuest()
    {
        return $this->role === 'guest';
    }

    // ==================== PROFILE METHODS ====================
    /**
     * Get User Information
     */
    public function getUserInfo()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'city' => $this->city,
            'created_at' => $this->created_at,
        ];
    }
    /**
     * Count total bookings
     */
    public function getTotalBookings()
    {
        return $this->bookings()->count();
    }
    /**
     * Get user's upcoming bookings (showtimes in the future)
     */
    public function getUpcomingBookings()
    {
        return $this->bookings()
            ->whereHas('showtime', function ($query) {
                $query->where('show_date', '>=', now()->toDateString())
                    ->orWhere(function ($q) {
                        $q->where('show_date', '=', now()->toDateString())
                            ->where('show_time', '>', now()->toTimeString());
                    });
            })
            ->with(['showtime.movie', 'showtime.room'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user's past bookings
     */
    public function getPastBookings()
    {
        return $this->bookings()
            ->whereHas('showtime', function ($query) {
                $query->where('show_date', '<', now()->toDateString())
                    ->orWhere(function ($q) {
                        $q->where('show_date', '=', now()->toDateString())
                            ->where('show_time', '<=', now()->toTimeString());
                    });
            })
            ->with(['showtime.movie', 'showtime.room'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
