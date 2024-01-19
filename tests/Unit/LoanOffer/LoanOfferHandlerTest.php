<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanOffer;

use App\Dto\LoanOfferResponse;
use App\LoanOffer\LoanOfferHandler;
use App\LoanOffer\Provider\LoanOfferProviderInterface;
use PHPUnit\Framework\TestCase;

class LoanOfferHandlerTest extends TestCase
{
    public function testHandlerWithoutProviders(): void
    {
        $loanOfferHandler = new LoanOfferHandler([]);

        $this->assertSame([], $loanOfferHandler->getOffers(amount: 1000));
    }

    public function testHandlerWithProviders(): void
    {
        $loanOfferHandler = new LoanOfferHandler([
            new class () implements LoanOfferProviderInterface {
                public static function getName(): string
                {
                    return 'Test';
                }

                public function getOffer(int $amount, ?int $months): LoanOfferResponse
                {
                    return new LoanOfferResponse(
                        providerName: 'Test',
                        requestSuccessful: true,
                        interestRate: 1.5,
                        months: 12
                    );
                }
            },
            new class () implements LoanOfferProviderInterface {
                public static function getName(): string
                {
                    return 'Test2';
                }

                public function getOffer(int $amount, ?int $months): LoanOfferResponse
                {
                    return new LoanOfferResponse(
                        providerName: 'Test2',
                        requestSuccessful: false,
                        interestRate: null,
                        months: null
                    );
                }
            }
        ]);

        $this->assertCount(2, $loanOfferHandler->getOffers(amount: 1000));
    }
}
