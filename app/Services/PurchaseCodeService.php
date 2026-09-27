<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to the author's license server (see license-server/ and
 * config/license.php). The server holds the Envato token, checks the sale
 * against Envato's Author API, and allows one live domain per purchase code;
 * localhost and private-network installs don't use it up.
 *
 * Nothing secret is stored on the buyer's install: only the purchase code
 * and what the server reported about it.
 */
class PurchaseCodeService
{
    public const CODE_PATTERN = '/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/i';

    /**
     * @return array{valid: bool, message: string, buyer?: ?string, license?: ?string, supported_until?: ?string}
     */
    public function verify(string $purchaseCode, string $domain): array
    {
        $purchaseCode = strtolower(trim($purchaseCode));

        if (! preg_match(self::CODE_PATTERN, $purchaseCode)) {
            return ['valid' => false, 'message' => 'That doesn\'t look like a valid purchase code.'];
        }

        return $this->call('activate', $purchaseCode, $domain);
    }

    /** Frees the code on the license server so it can be activated on another domain. */
    public function deactivate(string $purchaseCode, string $domain): array
    {
        return $this->call('deactivate', $purchaseCode, $domain);
    }

    /** Remembers a verified license on this install (storage/app/license.json). */
    public function store(string $purchaseCode, string $domain, array $details): void
    {
        File::ensureDirectoryExists(dirname(config('license.file')));
        File::put(config('license.file'), json_encode([
            'purchase_code' => strtolower(trim($purchaseCode)),
            'domain' => $domain,
            'buyer' => $details['buyer'] ?? null,
            'license' => $details['license'] ?? null,
            'supported_until' => $details['supported_until'] ?? null,
            'verified_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /** @return array<string, mixed>|null */
    public function current(): ?array
    {
        $file = config('license.file');
        if (! File::exists($file)) {
            return null;
        }

        $data = json_decode(File::get($file), true);

        return is_array($data) && ! empty($data['purchase_code']) ? $data : null;
    }

    public function forget(): void
    {
        File::delete(config('license.file'));
    }

    private function call(string $action, string $purchaseCode, string $domain): array
    {
        $url = rtrim((string) config('license.server'), '/').'/';

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(15)
                ->post($url, [
                    'action' => $action,
                    'purchase_code' => $purchaseCode,
                    'domain' => $domain,
                ]);
        } catch (ConnectionException $e) {
            // The buyer gets the friendly message; the real cause (DNS, SSL, timeout) goes to storage/logs.
            Log::warning('License server unreachable: '.$e->getMessage(), ['url' => $url, 'action' => $action]);

            return ['valid' => false, 'message' => 'Could not reach the license server. Check this server\'s internet connection and try again.'];
        }

        $body = $response->json();

        if (! is_array($body) || ! array_key_exists('valid', $body)) {
            return ['valid' => false, 'message' => 'The license server gave an unexpected response. Please try again shortly.'];
        }

        return [
            'valid' => (bool) $body['valid'] && $response->successful(),
            'message' => (string) ($body['message'] ?? ''),
            'buyer' => $body['buyer'] ?? null,
            'license' => $body['license'] ?? null,
            'supported_until' => $body['supported_until'] ?? null,
        ];
    }
}
