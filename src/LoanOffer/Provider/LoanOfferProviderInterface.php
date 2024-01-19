<?php

declare(strict_types=1);

namespace App\LoanOffer\Provider;

use App\Dto\LoanOfferResponse;

interface LoanOfferProviderInterface
{
    /**
     * Returns the offer conditions for the given amount and optional months.
     *
     * @param int $amount
     * @param int|null $months
     * @return LoanOfferResponse
     */
    public function getOffer(int $amount, ?int $months): LoanOfferResponse;

    /**
     * Returns the name of the load offer provider.
     *
     * @return string
     */
    public static function getName(): string;
}
