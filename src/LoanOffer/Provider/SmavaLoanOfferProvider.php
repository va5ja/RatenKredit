<?php

declare(strict_types=1);

namespace App\LoanOffer\Provider;

use App\Exception\CouldNotGetLoanOffer;
use NumberFormatter;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SmavaLoanOfferProvider extends AbstractLoanOfferProvider
{
    public const URL = 'https://api.jsonbin.io/v3/b/65a6e71e1f5677401f1ebd2c?meta=false';

    public static function getName(): string
    {
        return 'Smava';
    }

    protected function getClientResponse(int $amount, ?int $months): ResponseInterface
    {
        return $this->client->request(
            method: 'GET',
            url: self::URL,
            options: [
                'headers' => [
                    'X-Access-Key' => $this->accessKey,
                ],
            ]
        );
    }

    /**
     * @return array{interest_rate: float, months: integer}
     * @throws CouldNotGetLoanOffer
     */
    protected function normalizeConditions(array $conditions): array
    {
        if (!array_key_exists('Interest', $conditions) || !($conditions['Terms']['Duration'] ?? false)) {
            throw CouldNotGetLoanOffer::normalizationFailed(self::getName());
        }

        // parse e.g. string "3,5%" to float 3.5
        $interestRate = (new NumberFormatter(locale: 'de_DE', style: NumberFormatter::DECIMAL))
            ->parse($conditions['Interest']);
        // calculate the number of months from a string like "2 years 4 months"
        $interval = \DateInterval::createFromDateString($conditions['Terms']['Duration']);

        return [
            self::KEY_INTEREST_RATE => $interestRate,
            self::KEY_MONTHS => $interval->y * 12 + $interval->m,
        ];
    }
}
