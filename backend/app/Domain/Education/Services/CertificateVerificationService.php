<?php

namespace App\Domain\Education\Services;

use App\Domain\Education\Models\Certificate;
use App\Domain\Education\Models\Course;
use App\Models\User;
use Illuminate\Support\Str;

class CertificateVerificationService
{
    public function issueCertificate(User $user, Course $course, int $score = 100): Certificate
    {
        $year = date('Y');
        $randomPart = strtoupper(Str::random(6));
        $certNumber = "CEE-CERT-{$year}-{$randomPart}";
        $verificationCode = 'CEE-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        $timestamp = now()->timestamp;
        $hash = hash('sha256', "{$certNumber}:{$user->id}:{$course->id}:{$timestamp}:".config('app.key'));

        $university = $user->profile?->university ?? 'Campus Esports Ethiopia Academy';

        return Certificate::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'certificate_number' => $certNumber,
                'verification_code' => $verificationCode,
                'verification_hash' => $hash,
                'issued_at' => now(),
                'metadata' => [
                    'student_name' => $user->name,
                    'student_gamer_tag' => $user->profile?->gamer_tag ?? $user->name,
                    'university' => $university,
                    'course_title' => $course->title,
                    'course_title_am' => $course->title_am,
                    'category' => $course->category->value,
                    'score' => $score,
                    'issuer' => 'Campus Esports Ethiopia Digital Awareness Initiative',
                ],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $codeOrNumber): array
    {
        $codeOrNumber = trim($codeOrNumber);

        $cert = Certificate::with(['user.profile', 'course'])
            ->where('verification_code', $codeOrNumber)
            ->orWhere('certificate_number', $codeOrNumber)
            ->orWhere('verification_hash', $codeOrNumber)
            ->first();

        if (! $cert) {
            return [
                'is_valid' => false,
                'message' => 'Certificate could not be verified. Invalid verification code.',
            ];
        }

        return [
            'is_valid' => true,
            'message' => 'Official Certificate Verified Successfully',
            'certificate' => [
                'certificate_number' => $cert->certificate_number,
                'verification_code' => $cert->verification_code,
                'verification_hash' => $cert->verification_hash,
                'issued_at' => $cert->issued_at->toIso8601String(),
                'student_name' => $cert->metadata['student_name'] ?? $cert->user->name,
                'university' => $cert->metadata['university'] ?? ($cert->user->profile?->university ?? 'Campus Esports Ethiopia'),
                'course_title' => $cert->metadata['course_title'] ?? $cert->course->title,
                'course_title_am' => $cert->metadata['course_title_am'] ?? $cert->course->title_am,
                'score' => $cert->metadata['score'] ?? 100,
                'issuer' => $cert->metadata['issuer'] ?? 'Campus Esports Ethiopia',
            ],
        ];
    }
}
