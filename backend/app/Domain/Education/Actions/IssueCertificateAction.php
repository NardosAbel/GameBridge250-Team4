<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Models\Certificate;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Services\CertificateVerificationService;
use App\Models\User;

class IssueCertificateAction
{
    public function __construct(
        protected CertificateVerificationService $certService
    ) {}

    public function execute(User $user, Course $course, int $score = 100): Certificate
    {
        return $this->certService->issueCertificate($user, $course, $score);
    }
}
