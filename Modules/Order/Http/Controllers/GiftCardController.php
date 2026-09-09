<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Services\GiftCardService;

class GiftCardController extends Controller
{
    /**
     * Check gift card balance (JSON / AJAX).
     */
    public function check(Request $request, GiftCardService $giftCardService): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:32',
        ]);

        $result = $giftCardService->checkBalance($request->string('code'));
        $status = ($result['status'] === 'success') ? 200 : 422;

        return response()->json($result, $status);
    }
}
