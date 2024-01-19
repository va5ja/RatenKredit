<?php

declare(strict_types=1);

namespace App\Dto;

class LoanOfferResponse
{
    public function __construct(
        public readonly string $providerName,
        public readonly bool $requestSuccessful,
        public readonly ?float $interestRate = null,
        public readonly ?int $months = null
    ) {
    }
}
