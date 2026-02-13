<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Invitation;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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

        $email = Str::lower($request->email);
        $throttleKey = 'otp-send:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Too many OTP requests. Please try again later.',
                'retry_after_seconds' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }

        RateLimiter::hit($throttleKey, 15 * 60);

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
        $otp = (string) random_int(100000, 999999);

        // Store/update OTP
        Otp::updateOrCreate(
            ['email' => $email],
            [
                'user_id' => $user->id,
                'otp' => $otp,
                'attempts' => 0,
                'locked_until' => null,
                'expires_at' => now()->addMinutes(10),
            ]
        );

        // Send email (configure .env mail)
        Mail::send('emails.otp', ['otp' => $otp, 'user' => $user], function ($message) use ($email) {
            $message->to($email)->subject('Your Login OTP');
        });

        return response()->json([
            'message' => 'OTP sent successfully',
            'expires_in_seconds' => 600,
        ]);
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

        $email = Str::lower($request->email);

        $otpRecord = Otp::where('email', $email)->first();

        if (! $otpRecord) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired OTP']);
        }

        if ($otpRecord->locked_until && $otpRecord->locked_until->isFuture()) {
            return response()->json([
                'message' => 'Too many invalid attempts. Please request a new OTP later.',
                'locked_until' => $otpRecord->locked_until,
            ], 429);
        }

        if ($otpRecord->expires_at->isPast()) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired OTP']);
        }

        if (! hash_equals((string) $otpRecord->otp, (string) $request->otp)) {
            $otpRecord->increment('attempts');

            if ($otpRecord->attempts >= 3) {
                $otpRecord->update(['locked_until' => now()->addMinutes(15)]);
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

        $invitation = Invitation::where('token', $request->token)->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid or expired link'], 404);
        }

        if ($invitation->expires_at->isPast()) {
            return response()->json([
                'message' => 'This link has expired.',
                'resend_available' => true,
            ], 410);
        }

        if ($invitation->status === 'completed') {
            return response()->json([
                'message' => 'This assessment has already been completed.',
            ], 409);
        }

        $existingAttempt = Attempt::where('invitation_id', $invitation->id)->first();
        if (
            $existingAttempt
            && in_array($invitation->status, ['opened', 'started'], true)
            && $existingAttempt->status === 'in_progress'
        ) {
            return response()->json([
                'message' => 'This magic link has already been used. Please resend the link to continue.',
                'resend_available' => true,
            ], 409);
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
                'time_remaining' => $invitation->test->duration_minutes > 0
                    ? $invitation->test->duration_minutes * 60
                    : null,
            ]
        );

        // Keep magic-link sessions single-use at token level.
        $attempt->tokens()->delete();

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
     * Resend (refresh) candidate magic link.
     */
    public function resendMagicLink(Request $request)
    {
        $request->validate([
            'token' => 'nullable|string',
            'email' => 'nullable|email',
            'test_id' => 'nullable|uuid|exists:tests,id',
        ]);

        $invitation = null;

        if ($request->filled('token')) {
            $invitation = Invitation::where('token', $request->token)->first();
        }

        if (! $invitation && $request->filled('email') && $request->filled('test_id')) {
            $invitation = Invitation::where('candidate_email', Str::lower($request->email))
                ->where('test_id', $request->test_id)
                ->latest('created_at')
                ->first();
        }

        if (! $invitation) {
            return response()->json(['message' => 'Invitation not found'], 404);
        }

        if ($invitation->status === 'completed') {
            return response()->json(['message' => 'Assessment already completed'], 422);
        }

        $invitation->update([
            'token' => Str::random(64),
            'status' => 'sent',
            'sent_at' => now(),
            'opened_at' => null,
            'started_at' => null,
            'expires_at' => now()->addDays(7),
        ]);

        $frontendUrl = config('app.frontend_url') . '/test/' . $invitation->token;
        Mail::send('emails.invitation', ['invitation' => $invitation, 'url' => $frontendUrl], function ($message) use ($invitation) {
            $message->to($invitation->candidate_email)->subject('Your new assessment link');
        });

        return response()->json(['message' => 'A new link has been sent']);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

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
