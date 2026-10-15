<?php

namespace App\Http\Controllers\Api\V1\Education;

use App\Domain\Education\Models\Certificate;
use App\Domain\Education\Services\CertificateVerificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function verify(string $code, CertificateVerificationService $verificationService): JsonResponse
    {
        $result = $verificationService->verify($code);

        return response()->json([
            'success' => $result['is_valid'],
            'message' => $result['message'],
            'data' => $result,
        ], $result['is_valid'] ? 200 : 404);
    }

    public function myCertificates(Request $request): JsonResponse
    {
        $user = $request->user();

        $certificates = Certificate::with(['course:id,uuid,title,title_am,category,cover_image'])
            ->where('user_id', $user->id)
            ->latest('issued_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }

    public function show(int|string $id): JsonResponse
    {
        $cert = is_numeric($id)
            ? Certificate::with(['course', 'user.profile'])->findOrFail($id)
            : Certificate::with(['course', 'user.profile'])->where('uuid', $id)->orWhere('certificate_number', $id)->orWhere('verification_code', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $cert,
        ]);
    }
}
