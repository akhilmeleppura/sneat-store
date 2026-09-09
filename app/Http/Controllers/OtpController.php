<?php

namespace App\Http\Controllers;

use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function send(Request $request, OtpService $otpService): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => 'required|string|max:255',
            'type'       => 'nullable|string|max:50',
        ]);

        $type   = $validated['type'] ?? 'checkout';
        $result = $otpService->sendOtp($validated['identifier'], $type);

        return response()->json($result);
    }

    public function verify(Request $request, OtpService $otpService): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => 'required|string|max:255',
            'code'       => 'required|string|max:10',
            'type'       => 'nullable|string|max:50',
        ]);

        $type   = $validated['type'] ?? 'checkout';
        $result = $otpService->verifyOtp($validated['identifier'], $validated['code'], $type);

        $statusCode = ($result['status'] === 'success') ? 200 : 422;
        return response()->json($result, $statusCode);
    }
}
