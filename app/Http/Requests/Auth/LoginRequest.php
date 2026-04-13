<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Modules\Shared\Enums\DistributorStatus;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = array_merge($this->only('email', 'password'), [
            'is_active' => true,
        ]);

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $message = $this->resolveBlockedAccessMessage();

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $message ?? trans('auth.failed'),
            ]);
        }

        $user = $this->user();

        if ($user !== null && $this->isDistributorWithoutActiveCompany($user)) {
            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $this->distributorAccessMessage($user),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function resolveBlockedAccessMessage(): ?string
    {
        $email = Str::lower(trim((string) $this->input('email', '')));

        if ($email === '') {
            return null;
        }

        $user = User::query()
            ->with('distributor')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user || ! Hash::check((string) $this->input('password', ''), (string) $user->password)) {
            return null;
        }

        if (! $user->isActive()) {
            return trans('auth.inactive_user');
        }

        if ($this->isDistributorWithoutActiveCompany($user)) {
            return $this->distributorAccessMessage($user);
        }

        return null;
    }

    private function isDistributorWithoutActiveCompany(User $user): bool
    {
        if (! $user->isDistributor()) {
            return false;
        }

        if (! $user->distributor) {
            return false;
        }

        return ! $user->distributor->isActive();
    }

    private function distributorAccessMessage(User $user): string
    {
        $status = $user->distributor?->status;

        return match ($status) {
            DistributorStatus::PendingReview => trans('auth.company_pending_review'),
            DistributorStatus::Rejected => trans('auth.company_rejected'),
            DistributorStatus::Suspended => trans('auth.company_suspended'),
            default => trans('auth.company_inactive'),
        };
    }
}
