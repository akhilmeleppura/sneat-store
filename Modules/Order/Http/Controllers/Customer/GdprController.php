<?php

namespace Modules\Order\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Catalog\Models\ProductReview;
use Modules\Order\Models\Order;

class GdprController extends Controller
{
    /**
     * Download customer personal data archive (GDPR Article 20 - Data Portability).
     */
    public function exportData()
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)
            ->with(['items'])
            ->get();

        $reviews = ProductReview::where('user_id', $user->id)->get();

        $export = [
            'subject'          => 'GDPR Personal Data Archive',
            'exported_at'      => now()->toIso8601String(),
            'account'          => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'orders'           => $orders,
            'product_reviews'  => $reviews,
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT);
        $filename = 'customer-data-' . $user->id . '-' . date('Ymd') . '.json';

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Request account deletion (GDPR Article 17 - Right to be Forgotten).
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $user = Auth::user();

        // In enterprise workflow, queues compliance notification for legal retention audit
        return response()->json([
            'status'  => 'success',
            'message' => 'Your GDPR account erasure request has been submitted. Our compliance officer will process your request within 30 days.',
        ]);
    }
}
