<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Verifies a buyer's Envato purchase code against the Author API before the
 * installer is allowed to proceed — the anti-piracy gate Envato requires
 * for CodeCanyon items. See the build plan's "Installer & Purchase Code
 * Verification" section for the full flow.
 */
class PurchaseCodeService
{
    /**
     * @return array{valid: bool, message: string, sale?: array}
     */
    public function verify(string $purchaseCode, string $envatoPersonalToken): array
    {
        if (! preg_match('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/i', $purchaseCode)) {
            return ['valid' => false, 'message' => 'That doesn\'t look like a valid purchase code format.'];
        }

        $response = Http::withToken($envatoPersonalToken)
            ->timeout(10)
            ->get('https://api.envato.com/v3/market/author/sale', ['code' => $purchaseCode]);

        if ($response->status() === 404) {
            return ['valid' => false, 'message' => 'This purchase code was not found.'];
        }

        if (! $response->successful()) {
            return ['valid' => false, 'message' => 'Could not reach Envato right now — try again shortly.'];
        }

        $sale = $response->json();
        $expectedItemId = (string) config('services.envato.item_id');

        if ($expectedItemId && (string) ($sale['item']['id'] ?? '') !== $expectedItemId) {
            return ['valid' => false, 'message' => 'This purchase code is for a different item.'];
        }

        return ['valid' => true, 'message' => 'Purchase code verified.', 'sale' => $sale];
    }
}
