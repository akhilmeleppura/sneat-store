<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\GiftCard;
use Modules\Order\Services\GiftCardService;

class AdminGiftCardController extends Controller
{
    /**
     * Display all issued gift cards and metrics.
     */
    public function index(Request $request)
    {
        $query = GiftCard::withoutTenancy()->latest('id');

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'active') {
                $query->where('is_active', true)->where('current_balance', '>', 0);
            } elseif ($status === 'depleted') {
                $query->where('current_balance', '<=', 0);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'expired') {
                $query->whereNotNull('expires_at')->where('expires_at', '<', now());
            }
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('recipient_email', 'like', "%{$search}%");
            });
        }

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'data'   => $query->paginate(15),
            ]);
        }

        $giftCards = $query->paginate(15)->withQueryString();

        $stats = [
            'total_issued'    => GiftCard::withoutTenancy()->sum('initial_balance'),
            'active_balance'  => GiftCard::withoutTenancy()->where('is_active', true)->sum('current_balance'),
            'active_cards'    => GiftCard::withoutTenancy()->where('is_active', true)->where('current_balance', '>', 0)->count(),
            'depleted_cards'  => GiftCard::withoutTenancy()->where('current_balance', '<=', 0)->count(),
        ];

        return view('order::admin.gift_cards.index', compact('giftCards', 'stats'));
    }

    /**
     * Issue a new gift card.
     */
    public function store(Request $request, GiftCardService $giftCardService)
    {
        $validated = $request->validate([
            'amount'          => 'required|numeric|min:1|max:5000',
            'currency'        => 'nullable|string|max:5',
            'recipient_email' => 'nullable|email|max:255',
            'valid_days'      => 'nullable|integer|min:1|max:3650',
        ]);

        $currency = $validated['currency'] ?? 'USD';
        $validDays = $validated['valid_days'] ?? 365;

        $card = $giftCardService->issueGiftCard(
            (float) $validated['amount'],
            $currency,
            $validated['recipient_email'] ?? null,
            $validDays
        );

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status'  => 'success',
                'message' => "Gift card {$card->code} issued successfully.",
                'card'    => $card,
            ]);
        }

        return redirect()->back()
            ->with('success', "Gift Card {$card->code} with balance \${$card->initial_balance} issued successfully.");
    }

    /**
     * Toggle active state of a gift card.
     */
    public function toggle(int $id)
    {
        $card = GiftCard::withoutTenancy()->findOrFail($id);
        $card->update(['is_active' => !$card->is_active]);

        $statusStr = $card->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Gift card {$card->code} {$statusStr}.");
    }
}
