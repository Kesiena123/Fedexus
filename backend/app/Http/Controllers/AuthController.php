<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\NotificationDispatchService;
use App\Support\AdminSecurity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function adminCaptcha(): JsonResponse
    {
        $left = random_int(2, 9);
        $right = random_int(2, 9);
        $token = Str::uuid()->toString();

        Cache::put($this->captchaCacheKey($token), Hash::make((string) ($left + $right)), now()->addSeconds(max(60, (int) config('admin.captcha_ttl_seconds', 300))));

        return response()->json([
            'token' => $token,
            'prompt' => sprintf('What is %d + %d?', $left, $right),
            'expires_in' => (int) config('admin.captcha_ttl_seconds', 300),
        ]);
    }

    public function adminLogin(Request $request, NotificationDispatchService $notifications, AdminAuditLogger $audit): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'captcha_token' => ['required', 'string'],
            'captcha_answer' => ['required', 'string', 'max:10'],
            'device_name' => ['required', 'string', 'max:120'],
            'device_fingerprint' => ['required', 'string', 'max:128'],
        ]);

        abort_unless($this->validCaptcha($credentials['captcha_token'], $credentials['captcha_answer']), 422, 'Invalid CAPTCHA response.');

        $rateLimitKey = sprintf('admin-login:%s:%s', strtolower($credentials['email']), $request->ip());

        abort_if(RateLimiter::tooManyAttempts($rateLimitKey, (int) config('admin.login_max_attempts', 5)), 429, 'Too many admin login attempts. Try again later.');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! AdminSecurity::isAdminRole($user->role)) {
            RateLimiter::hit($rateLimitKey, (int) config('admin.login_decay_seconds', 900));
            abort_unless($user && Hash::check($credentials['password'], $user->password), 422, 'Invalid credentials.');
            abort_if($user->is_suspended, 403, 'This account is suspended. Contact a super administrator.');
            abort_unless(AdminSecurity::isAdminRole($user->role), 403, 'Admin access is required.');
        }

        RateLimiter::clear($rateLimitKey);

        $recognizedDevice = $this->hasRecognizedDevice($user, $credentials['device_fingerprint']);

        if ($user->two_factor_enabled) {
            $this->sendTwoFactorCode($user, $notifications);

            return response()->json([
                'requires_two_factor' => true,
                'message' => 'A two-factor code was sent to your notification channels.',
                'device_recognized' => $recognizedDevice,
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken(
            $credentials['device_name'],
            [AdminSecurity::TOKEN_ABILITY],
            now()->addMinutes(max(1, (int) config('admin.session_timeout_minutes', 30)))
        )->plainTextToken;

        $audit->log($request, $user, 'admin.login', [
            'target_type' => 'user',
            'target_id' => $user->id,
            'metadata' => [
                'device_recognized' => $recognizedDevice,
            ],
        ]);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'session_timeout_minutes' => (int) config('admin.session_timeout_minutes', 30),
            'device_recognized' => $recognizedDevice,
        ]);
    }

    public function confirmTwoFactorLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'guard' => ['nullable', 'in:web,admin'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'device_fingerprint' => ['nullable', 'string', 'max:128'],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();

        abort_unless($this->validTwoFactorCode($user, $data['code']), 422, 'Invalid or expired two-factor code.');
        abort_if($user->is_suspended, 403, 'This account is suspended.');
        abort_if(($data['guard'] ?? 'web') === 'admin' && ! AdminSecurity::isAdminRole($user->role), 403, 'Admin access is required.');
        abort_if(($data['guard'] ?? 'web') === 'web' && AdminSecurity::isAdminRole($user->role), 403, 'Use the admin sign in page for operations access.');

        $user->forceFill([
            'two_factor_code_hash' => null,
            'two_factor_expires_at' => null,
            'last_login_at' => now(),
        ])->save();

        $isAdminGuard = ($data['guard'] ?? 'web') === 'admin';
        $tokenName = $isAdminGuard ? ($data['device_name'] ?? 'admin-device') : ($data['guard'] ?? 'web');
        $abilities = $isAdminGuard ? [AdminSecurity::TOKEN_ABILITY] : ['*'];
        $expiresAt = $isAdminGuard ? now()->addMinutes(max(1, (int) config('admin.session_timeout_minutes', 30))) : null;

        return response()->json([
            'user' => $user,
            'token' => $user->createToken($tokenName, $abilities, $expiresAt)->plainTextToken,
            'session_timeout_minutes' => $isAdminGuard ? (int) config('admin.session_timeout_minutes', 30) : null,
        ]);
    }

    public function enableTwoFactor(Request $request, NotificationDispatchService $notifications): JsonResponse
    {
        $this->sendTwoFactorCode($request->user(), $notifications);

        return response()->json(['message' => 'A confirmation code was sent to your notification channels.']);
    }

    public function confirmTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);
        $user = $request->user();

        abort_unless($this->validTwoFactorCode($user, $data['code']), 422, 'Invalid or expired two-factor code.');

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_code_hash' => null,
            'two_factor_expires_at' => null,
        ])->save();

        return response()->json(['message' => 'Two-factor authentication is enabled.']);
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();

        abort_unless(Hash::check($data['password'], $user->password), 422, 'Invalid password.');

        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_code_hash' => null,
            'two_factor_expires_at' => null,
        ])->save();

        return response()->json(['message' => 'Two-factor authentication is disabled.']);
    }

    public function logout(Request $request, AdminAuditLogger $audit): JsonResponse
    {
        if (AdminSecurity::isAdminUser($request->user()) && $request->user()->currentAccessToken()?->can(AdminSecurity::TOKEN_ABILITY)) {
            $audit->log($request, $request->user(), 'admin.logout');
        }

        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    private function sendTwoFactorCode(User $user, NotificationDispatchService $notifications): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'two_factor_code_hash' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        $notifications->send(
            $user,
            'Your FreightFlow security code',
            'Use two-factor code '.$code.' within 10 minutes.',
            ['type' => 'two_factor'],
            ['in_app', 'email']
        );
    }

    private function validTwoFactorCode(User $user, string $code): bool
    {
        return (bool) (
            $user->two_factor_code_hash
            && $user->two_factor_expires_at?->isFuture()
            && Hash::check($code, $user->two_factor_code_hash)
        );
    }

    private function captchaCacheKey(string $token): string
    {
        return 'admin-captcha:'.$token;
    }

    private function validCaptcha(string $token, string $answer): bool
    {
        $hashedAnswer = Cache::pull($this->captchaCacheKey($token));

        return is_string($hashedAnswer) && Hash::check(trim($answer), $hashedAnswer);
    }

    private function hasRecognizedDevice(User $user, string $deviceFingerprint): bool
    {
        return AdminAuditLog::query()
            ->where('admin_user_id', $user->id)
            ->where('action', 'admin.login')
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('created_at', '>=', now()->subDays(max(1, (int) config('admin.trusted_device_window_days', 30))))
            ->exists();
    }
}
