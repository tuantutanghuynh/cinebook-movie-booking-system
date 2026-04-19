<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Mail\WelcomeMail;

/**
 * LoginController
 * 
 * Handles user authentication operations including:
 * - Login form display
 * - User authentication and validation
 * - Session management
 * - Role-based redirects (admin/user)
 * - Logout functionality
 */
class LoginController extends Controller
{
    //Show login form
    public function showloginForm()
    {
        return view('login.login');
    }

    //Handle login request
    public function login(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Trim whitespace
        $email = trim($validated['email']);
        $password = $validated['password'];
        //2. find user in database with email
        $user = User::where('email', $email)->first();
        //3. check if user exists and password matches
        if ($user && $password === $user->password) {
            //4. log the user in using Laravel Auth
            Auth::login($user);
            // Also keep session for backward compatibility with existing code
            Session::put('user_id', $user->id);
            Session::put('user_name', $user->name);
            
            //5. redirect to intended URL or homepage
            $intendedUrl = session('intended_url', '/');
            session()->forget('intended_url'); // Clear intended URL after use
            
            return redirect($intendedUrl);
        } else {
            //6. redirect back with error
            return redirect('/login')->with('error', 'Invalid credentials');
        }
    }
    //Handle logout request
    public function logout()
    {
        // Laravel Auth logout
        Auth::logout();
        
        // Clear session
        Session::forget('user_id');
        Session::forget('user_name');
        
        return redirect('/');
    }
    //Show registration form
    public function showRegisterForm()
    {
        return view('login.register');
    }
    //Handle registration request
    public function register(Request $request)
    {
        // Validate and sanitize input
        $validated = $request->validate([
            'name' => 'required|string|max:100|regex:/^[^\s].*[^\s]$/|regex:/^(?!.*\s{2}).*$/',
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8|regex:/^\S+$/',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
        ], [
            'name.regex' => 'Name cannot start or end with spaces, or contain consecutive spaces',
            'password.regex' => 'Password cannot contain spaces',
            'email.email' => 'Please enter a valid email address',
        ]);

        // Trim whitespace from inputs
        $name = trim($validated['name']);
        $email = trim($validated['email']);
        $password = $validated['password']; // No trim needed due to regex validation
        $city = isset($validated['city']) ? trim($validated['city']) : null;
        $phone = isset($validated['phone']) ? trim($validated['phone']) : null;
        //2. check if user with email already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return redirect('/register')->with('error', 'Email already registered');
        }
        //3. check if user with phone already exists
        $existingPhone = User::where('phone', $phone)->first();
        if ($existingPhone) {
            return redirect('/register')->with('error', 'Phone number already registered');
        }
        //4. create new user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // storing plain text per request
            'city' => $city,
            'phone' => $phone,
            'role' => 'user' // default role
        ]);

        //5. send welcome email
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Exception $e) {
            // Log error but don't stop registration
            Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        //6. log the user in using Laravel Auth
        Auth::login($user);
        // Also keep session for backward compatibility
        Session::put('user_id', $user->id);
        Session::put('user_name', $user->name);
        
        //7. redirect to intended URL or homepage
        $intendedUrl = session('intended_url', '/');
        session()->forget('intended_url'); // Clear intended URL after use
        
        return redirect($intendedUrl);
    }

    public function apiLogin(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', trim($request->email))->first();

        if(!$user || $request->password !== $user->password){
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name'=> $user->name,
                'email'=> $user->email,
                'role'=> $user->role,
            ]
        ]);
        
    }

    public function apiLogout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => "Loggout successfull!"]);
    }
}