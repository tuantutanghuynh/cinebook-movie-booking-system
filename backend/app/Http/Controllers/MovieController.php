<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Movie;
use App\Models\Genre;
use Carbon\Carbon;

/**
 * MovieController
 * 
 * Handles public movie display and interaction operations including:
 * - Homepage movie listings with viral score algorithm
 * - Movie details with reviews and recommendations
 * - Now showing and coming soon movie pages
 * - Genre-based movie filtering
 * - Movie search functionality
 * - Review aggregation and display
 */
class MovieController extends Controller
{
    /**
     * Helper function to attach genres to movies (using Eloquent relationships)
     */
    private function attachGenresToMovies($movies)
    {
        // Eager load genres for all movies
        $movieIds = collect($movies)->pluck('id')->toArray();

        $moviesWithGenres = Movie::with('genres')
            ->whereIn('id', $movieIds)
            ->get()
            ->keyBy('id');

        // Attach genres to each movie
        foreach ($movies as $movie) {
            $movieModel = $moviesWithGenres->get($movie->id);
            $movie->genres = $movieModel ? $movieModel->genres->pluck('name')->toArray() : [];
        }

        return $movies;
    }
    /**
     * Helper function to attach age rating descriptions to movies
     * Vietnamese film rating system
     */
    private function attachAgeRatingsToMovies($movies)
    {
        $ageRatings = [
            'P' => 'General Audiences - Suitable for all ages',
            'T13' => 'Parental Guidance Suggested - For ages 13 and above',
            'T16' => 'Parents Strongly Cautioned - For ages 16 and above',
            'T18' => 'Restricted - For ages 18 and above only',
            'C' => 'Adults Only - Mature content, 18+ strictly enforced'
        ];

        foreach ($movies as $movie) {
            $movie->age_rating_description = $ageRatings[$movie->age_rating] ?? 'Not Rated';
        }
        return $movies;
    }
    //Homepage function - show movies on homepage
    public function homepage()
    {
        // Auto-update movie statuses based on real-time
        $this->updateMovieStatuses();

        // Get viral/trending "Now Showing" movies based on:
        // 1. Number of bookings (popularity)
        // 2. Number of reviews (engagement)
        // 3. Average rating (quality)
        $movies = DB::table('movies')
            ->select([
                'movies.*',
                DB::raw('COALESCE(booking_count, 0) as popularity_score'),
                DB::raw('COALESCE(review_count, 0) as engagement_score'),
                DB::raw('COALESCE(movies.rating_avg, 0) as quality_score'),
                DB::raw('(COALESCE(booking_count, 0) * 0.4 + COALESCE(review_count, 0) * 0.3 + COALESCE(movies.rating_avg, 0) * 0.3) as viral_score')
            ])
            ->leftJoin(DB::raw('(SELECT 
                    s.movie_id, 
                    COUNT(DISTINCT b.id) as booking_count
                FROM showtimes s
                LEFT JOIN bookings b ON s.id = b.showtime_id AND b.status = "confirmed"
                GROUP BY s.movie_id
            ) as booking_stats'), 'movies.id', '=', 'booking_stats.movie_id')
            ->leftJoin(DB::raw('(SELECT 
                    movie_id, 
                    COUNT(*) as review_count
                FROM reviews
                GROUP BY movie_id
            ) as review_stats'), 'movies.id', '=', 'review_stats.movie_id')
            ->where('movies.status', 'now_showing')
            ->orderByDesc('viral_score')
            ->orderByDesc('movies.rating_avg')
            ->limit(6)
            ->get();

        //upcoming movies - ordered by release date
        $upcomingMovies = DB::table('movies')
            ->where('status', 'coming_soon')
            ->orderBy('release_date', 'asc')
            ->limit(6)->get(); // fetch only 6 upcoming movies for homepage

        // Attach genres using helper function
        $movies = $this->attachGenresToMovies($movies);
        $upcomingMovies = $this->attachGenresToMovies($upcomingMovies);
        // Attach age ratings using helper function
        $movies = $this->attachAgeRatingsToMovies($movies);
        $upcomingMovies = $this->attachAgeRatingsToMovies($upcomingMovies);

        if (request()->expectsJson()) {
            return response()->json([
                'movies' => $movies,
                'upcomingMovies' => $upcomingMovies,
            ]);
        }
        return view('homepage', compact('movies', 'upcomingMovies'));
    }


    /**
     * Auto-update movie statuses based on current date and time
     */
    private function updateMovieStatuses()
    {
        $now = Carbon::now();

        // Update coming_soon to now_showing for movies released today or before
        // (but only if they haven't been manually ended)
        Movie::where('status', 'coming_soon')
            ->where('release_date', '<=', $now->toDateString())
            ->update(['status' => 'now_showing']);
    }

    //1. movie function to fetch all movies from the database and return to index view
    public function index()
    {
        $movies = DB::table('movies')->get();

        // Attach genres using helper function
        $movies = $this->attachGenresToMovies($movies);

        return view('index', compact('movies'));
    }
    public function show(Request $request, $id)
    {
        // Auto-update movie statuses based on real-time
        $this->updateMovieStatuses();

        // Use Eloquent Model instead of DB::table to enable relationships
        $movie = Movie::with('genres', 'reviews.user')->findOrFail($id);

        // Get genres for this movie using relationships
        if ($movie) {
            $movie->genres = $movie->genres->pluck('name')->toArray();
        }

        // Get review sort parameter (default: latest)
        $reviewSort = $request->input('review_sort', 'latest');

        // Check if user can review this movie
        $canReview = false;
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'admin') {
                $canReview = true;
            } else {
                $userId = $user->id;
                // Check if user has watched this movie (showtime must be in the past)
                $hasWatched = DB::table('booking_seats')
                    ->join('showtimes', 'booking_seats.showtime_id', '=', 'showtimes.id')
                    ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
                    ->where('bookings.user_id', $userId)
                    ->where('showtimes.movie_id', $id)
                    ->where('bookings.payment_status', 'paid')
                    ->where(function ($query) {
                        $query->where('showtimes.show_date', '<', now()->toDateString())
                            ->orWhere(function ($q) {
                                $q->where('showtimes.show_date', '=', now()->toDateString())
                                    ->where('showtimes.show_time', '<', now()->toTimeString());
                            });
                    })
                    ->exists();

                // Check if user has already reviewed
                $hasReviewed = $movie->reviews->where('user_id', $userId)->isNotEmpty();

                $canReview = $hasWatched && !$hasReviewed;
            }
        }

        return view('movie_details', compact('movie', 'canReview', 'reviewSort'));
    }
    //2. upcomingMovies function to fetch upcoming movies from the database and return to upcoming_movies view
    public function upcomingMovies(Request $request)
    {
        // Auto-update movie statuses based on real-time
        $this->updateMovieStatuses();

        $query = DB::table('movies')->where('status', 'coming_soon');

        // Filter: Genre
        $genreId = $request->input('genre');
        if ($genreId) {
            $movieIds = DB::table('movie_genres')->where('genre_id', $genreId)->pluck('movie_id');
            $query->whereIn('id', $movieIds);
        }

        // Filter: Language
        $language = $request->input('language');
        if ($language) {
            $query->where('language', $language);
        }

        // Filter: Duration (min, max)
        $durationMin = $request->input('duration_min');
        $durationMax = $request->input('duration_max');
        if ($durationMin) {
            $query->where('duration', '>=', $durationMin);
        }
        if ($durationMax) {
            $query->where('duration', '<=', $durationMax);
        }

        // Filter: Release Date (from, to)
        $releaseFrom = $request->input('release_from');
        $releaseTo = $request->input('release_to');
        if ($releaseFrom) {
            $query->where('release_date', '>=', $releaseFrom);
        }
        if ($releaseTo) {
            $query->where('release_date', '<=', $releaseTo);
        }

        // Sort only when option is selected
        $sort = $request->input('sort');
        if ($sort) {
            switch ($sort) {
                case 'name_asc':
                    $query->orderBy('title', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('title', 'desc');
                    break;
                case 'release_asc':
                    $query->orderBy('release_date', 'asc');
                    break;
                case 'release_desc':
                    $query->orderBy('release_date', 'desc');
                    break;
                case 'rating_asc':
                    $query->orderBy('rating_avg', 'asc');
                    break;
                case 'rating_desc':
                    $query->orderBy('rating_avg', 'desc');
                    break;
                case 'duration_asc':
                    $query->orderBy('duration', 'asc');
                    break;
                case 'duration_desc':
                    $query->orderBy('duration', 'desc');
                    break;
            }
        }

        $movies = $query->get();
        $movies = $this->attachGenresToMovies($movies);

        // Attach age ratings using helper function
        $movies = $this->attachAgeRatingsToMovies($movies);

        // For filter dropdowns
        $genres = \App\Models\Genre::all();
        $languages = DB::table('movies')->where('status', 'coming_soon')->distinct()->pluck('language');

        return view('movie.upcoming_movies', compact('movies', 'genres', 'languages'));
    }
    //3. nowShowing function to fetch now showing movies from the database and return to now_showing view
    public function nowShowing(Request $request)
    {
        // Auto-update movie statuses based on real-time
        $this->updateMovieStatuses();

        $query = DB::table('movies')->where('status', 'now_showing');

        // Filter: Genre
        $genreId = $request->input('genre');
        if ($genreId) {
            $movieIds = DB::table('movie_genres')->where('genre_id', $genreId)->pluck('movie_id');
            $query->whereIn('id', $movieIds);
        }

        // Filter: Language
        $language = $request->input('language');
        if ($language) {
            $query->where('language', $language);
        }

        // Filter: Duration (min, max)
        $durationMin = $request->input('duration_min');
        $durationMax = $request->input('duration_max');
        if ($durationMin) {
            $query->where('duration', '>=', $durationMin);
        }
        if ($durationMax) {
            $query->where('duration', '<=', $durationMax);
        }

        // Filter: Release Date (from, to)
        $releaseFrom = $request->input('release_from');
        $releaseTo = $request->input('release_to');
        if ($releaseFrom) {
            $query->where('release_date', '>=', $releaseFrom);
        }
        if ($releaseTo) {
            $query->where('release_date', '<=', $releaseTo);
        }

        // Sort only when option is selected
        $sort = $request->input('sort');
        if ($sort) {
            switch ($sort) {
                case 'name_asc':
                    $query->orderBy('title', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('title', 'desc');
                    break;
                case 'release_asc':
                    $query->orderBy('release_date', 'asc');
                    break;
                case 'release_desc':
                    $query->orderBy('release_date', 'desc');
                    break;
                case 'rating_asc':
                    $query->orderBy('rating_avg', 'asc');
                    break;
                case 'rating_desc':
                    $query->orderBy('rating_avg', 'desc');
                    break;
                case 'duration_asc':
                    $query->orderBy('duration', 'asc');
                    break;
                case 'duration_desc':
                    $query->orderBy('duration', 'desc');
                    break;
            }
        }

        $movies = $query->get();
        $movies = $this->attachGenresToMovies($movies);

        //Attach age ratings using helper function
        $movies = $this->attachAgeRatingsToMovies($movies);

        // For filter dropdowns
        $genres = \App\Models\Genre::all();
        $languages = DB::table('movies')->where('status', 'now_showing')->distinct()->pluck('language');

        return view('movie.now_showing', compact('movies', 'genres', 'languages'));
    }

    /**
     * Display promotions page
     * 
     * Shows all available cinema promotions and special offers
     * organized in tabbed categories for easy navigation
     * 
     * @return \Illuminate\View\View
     */
    public function promotions()
    {
        return view('promotions');
    }

    /**
     * Display sitemap page
     * 
     * Shows complete site structure and navigation
     * for easy access to all pages and features
     * 
     * @return \Illuminate\View\View
     */
    public function sitemap()
    {
        return view('sitemap');
    }
}
