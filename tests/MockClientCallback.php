<?php

declare(strict_types=1);

namespace App\Tests;

use App\LoanOffer\Provider\IngLoanOfferProvider;
use App\LoanOffer\Provider\SmavaLoanOfferProvider;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MockClientCallback
{
    public function __invoke(string $method, string $url, array $options = []): ResponseInterface
    {
        if (str_contains($url, IngLoanOfferProvider::URL . '2000')) {
            return new JsonMockResponse([
                'status' => 'error'
            ]);
        }

        if (str_contains($url, IngLoanOfferProvider::URL)) {
            return new JsonMockResponse([
                'zinsen' => 3.5,
                'duration' => 24
            ]);
        }

        if (str_contains($url, SmavaLoanOfferProvider::URL)) {
            return new JsonMockResponse([
                'Interest' => '3,5%',
                'Terms' => [
                    'Duration' => '2 years'
                ]
            ]);
        }

        return new MockResponse();
    }
}
