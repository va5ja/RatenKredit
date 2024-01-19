<?php

declare(strict_types=1);

namespace App\LoanOffer;

use App\Dto\LoanOfferResponse;
use App\LoanOffer\Provider\LoanOfferProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class LoanOfferHandler
{
    /**
     * @param iterable<LoanOfferProviderInterface> $providers
     */
    public function __construct(
        #[TaggedIterator('app.loan_offer_provider', defaultIndexMethod: 'getName')]
        private readonly iterable $providers
    ) {
    }

    /**
     * @return LoanOfferResponse[]
     */
    public function getOffers(int $amount, ?int $months = null): array
    {
        $offers = [];
        foreach ($this->providers as $provider) {
            $offers[] = $provider->getOffer($amount, $months);
        }

        return $offers;
    }
}
