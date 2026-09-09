<?php

namespace Modules\Order\Services;

use Illuminate\Support\Str;
use Modules\Order\Models\GiftCard;

class GiftCardService
{
    /**
     * Issue a new digital gift card with unique 16-character code.
     */
    public function issueGiftCard(float $amount, string $currency = 'USD', ?string $recipientEmail = null, ?int $validDays = 365): GiftCard
    {
        $code = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));

        return GiftCard::create([
            'tenant_id'       => \Modules\Context\Facades\Context::tenantId() ?? 1,
            'code'            => $code,
            'initial_balance' => $amount,
            'current_balance' => $amount,
            'currency'        => strtoupper($currency),
            'recipient_email' => $recipientEmail,
            'is_active'       => true,
            'expires_at'      => $validDays ? now()->addDays($validDays) : null,
        ]);
    }

    /**
     * Verify gift card and check balance.
     */
    public function checkBalance(string $code): array
    {
        $card = GiftCard::withoutTenancy()->where('code', trim($code))->first();

        if (!$card) {
            return [
                'status'  => 'error',
                'message' => 'Invalid gift card code.',
            ];
        }

        if (!$card->isValid()) {
            return [
                'status'  => 'error',
                'message' => 'Gift card is expired or has zero remaining balance.',
                'balance' => (float) $card->current_balance,
            ];
        }

        return [
            'status'          => 'success',
            'code'            => $card->code,
            'current_balance' => (float) $card->current_balance,
            'formatted'       => money($card->current_balance, $card->currency),
            'expires_at'      => $card->expires_at?->toDateString(),
        ];
    }

    /**
     * Deduct used balance from gift card.
     */
    public function deductBalance(string $code, float $amountToDeduct): bool
    {
        $card = GiftCard::withoutTenancy()->where('code', trim($code))->first();
        if (!$card || !$card->isValid() || $amountToDeduct <= 0) {
            return false;
        }

        $newBalance = max(0, (float) $card->current_balance - $amountToDeduct);
        $card->update(['current_balance' => $newBalance]);

        return true;
    }
}

