<?php

namespace App\Domain\Security\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_TIMEOUT = 'timeout';

    public const STATUS_BYPASSED = 'bypassed';

    public function __construct(
        private readonly string $secretKey,
        private readonly bool $enabled,
        private readonly bool $bypassLocal
    ) {}

    /**
     * Resolve dependencies from config.
     */
    public static function make(): self
    {
        return new self(
            // Cast to string: config returns null (not the '' default) when the
            // TURNSTILE_SECRET_KEY env var is unset, e.g. in CI. The constructor
            // type-hints a non-null string, so a null would TypeError before the
            // enabled/bypass checks in validateToken ever run.
            secretKey: (string) config('services.turnstile.secret_key', ''),
            enabled: (bool) config('services.turnstile.enabled', false),
            bypassLocal: (bool) config('services.turnstile.bypass_local', true)
        );
    }

    /**
     * Validate the Turnstile token against Cloudflare API.
     *
     * @return array{status: string, message: string}
     */
    public function validateToken(?string $token, ?string $ip = null): array
    {
        if (! $this->enabled) {
            return [
                'status' => self::STATUS_BYPASSED,
                'message' => 'Turnstile is globally disabled via configuration.',
            ];
        }

        if ($this->bypassLocal && app()->environment('local', 'testing')) {
            return [
                'status' => self::STATUS_BYPASSED,
                'message' => 'Turnstile validation bypassed for local environment.',
            ];
        }

        if (empty($token)) {
            return [
                'status' => self::STATUS_INVALID,
                'message' => 'Turnstile token is missing.',
            ];
        }

        try {
            // Cloudflare API documentation specifies a POST request
            $response = Http::asForm()
                ->timeout(5) // Graceful timeout
                ->retry(2, 100) // Retry-safe architecture
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

            if ($response->failed()) {
                Log::warning('Cloudflare Turnstile API non-200 response.', ['status' => $response->status(), 'body' => $response->body()]);

                return [
                    'status' => self::STATUS_TIMEOUT, // Treat API failures as timeouts for fail-open logic
                    'message' => 'Turnstile validation service unavailable.',
                ];
            }

            $result = $response->json();

            if (! empty($result['success']) && $result['success'] === true) {
                return [
                    'status' => self::STATUS_SUCCESS,
                    'message' => 'Token validated successfully.',
                ];
            }

            // Validation failed
            Log::info('Turnstile validation failed.', ['errors' => $result['error-codes'] ?? []]);

            return [
                'status' => self::STATUS_INVALID,
                'message' => 'Turnstile verification failed.',
            ];

        } catch (ConnectionException $e) {
            Log::error('Turnstile connection timeout/error.', ['exception' => $e->getMessage()]);

            return [
                'status' => self::STATUS_TIMEOUT,
                'message' => 'Turnstile validation service timed out.',
            ];
        } catch (\Throwable $e) {
            Log::error('Turnstile unexpected error.', ['exception' => $e->getMessage()]);

            return [
                'status' => self::STATUS_TIMEOUT,
                'message' => 'Turnstile validation service encountered an error.',
            ];
        }
    }
}
