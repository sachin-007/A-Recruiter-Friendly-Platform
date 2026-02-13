<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Send OTP to email (for recruiters/authors/admins)
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)
                    ->whereIn('role', ['admin', 'recruiter', 'author'])
                    ->first();

        if (!$user || !$user->is_active) {
            return response()->json(['message' => 'Unauthorized or inactive user'], 403);
        }

        // Check if user is locked out
        $existingOtp = Otp::where('email', $request->email)->first();
        if ($existingOtp && $existingOtp->locked_until && $existingOtp->locked_until->isFuture()) {
            return response()->json([
                'message' => 'Too many attempts. Try again later.',
                'locked_until' => $existingOtp->locked_until,
            ], 429);
        }

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store/update OTP
        Otp::updateOrCreate(
            ['email' => $request->email],
            [
                'user_id' => $user->id,
                'otp' => $otp,
                'attempts' => 0,
                'locked_until' => null,
                'expires_at' => now()->addMinutes(10),
            ]
        );

        // Send email (configure .env mail)
        Mail::send('emails.otp', ['otp' => $otp, 'user' => $user], function ($message) use ($request) {
            $message->to($request->email)->subject('Your Login OTP');
        });

        return response()->json(['message' => 'OTP sent successfully']);
    }

    /**
     * Verify OTP and issue Sanctum token
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $otpRecord = Otp::where('email', $request->email)
                        ->where('otp', $request->otp)
                        ->where('expires_at', '>', now())
                        ->first();

        if (!$otpRecord) {
            // Increment attempts and maybe lock
            $otp = Otp::where('email', $request->email)->first();
            if ($otp) {
                $otp->increment('attempts');
                if ($otp->attempts >= 5) {
                    $otp->update(['locked_until' => now()->addMinutes(15)]);
                }
            }
            throw ValidationException::withMessages(['otp' => 'Invalid or expired OTP']);
        }

        $user = $otpRecord->user;

        // Delete used OTP
        $otpRecord->delete();

        // Create Sanctum token with role-based abilities
        $abilities = [$user->role];
        $token = $user->createToken('auth-token', $abilities)->plainTextToken;

        // Load organization for frontend context
        $user->load('organization');

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Verify magic link (for candidates)
     */
    public function verifyMagicLink(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $invitation = Invitation::where('token', $request->token)
                                ->where('expires_at', '>', now())
                                ->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid or expired link'], 404);
        }

        // Mark as opened if not already
        if ($invitation->status === 'sent') {
            $invitation->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
        }

        // Find or create an attempt
        $attempt = Attempt::firstOrCreate(
            ['invitation_id' => $invitation->id],
            [
                'test_id' => $invitation->test_id,
                'candidate_email' => $invitation->candidate_email,
                'candidate_name' => $invitation->candidate_name,
                'status' => 'in_progress',
            ]
        );

        // Generate a token limited to candidate role
        $token = $attempt->createToken('candidate-token', ['candidate'])->plainTextToken;

        // Load test with sections and questions for the frontend
        $attempt->load('test.sections.questions');

        return response()->json([
            'token' => $token,
            'attempt' => $attempt,
            'invitation' => $invitation,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load('organization');
        return response()->json($user);
    }

    /**
     * Logout (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}