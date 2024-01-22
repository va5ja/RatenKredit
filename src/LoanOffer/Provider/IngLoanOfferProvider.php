<?php

declare(strict_types=1);

namespace App\LoanOffer\Provider;

use App\Exception\CouldNotGetLoanOffer;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IngLoanOfferProvider extends AbstractLoanOfferProvider
{
    public const URL = 'https://api.jsonbin.io/v3/b/65a6e50e266cfc3fde79aa14?meta=false&amount=';

    public static function getName(): string
    {
        return 'Ing';
    }

    protected function getClientResponse(int $amount, ?int $months): ResponseInterface
    {
        return $this->client->request(
            method: 'GET',
            url: self::URL . $amount,
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
        if (!array_key_exists('zinsen', $conditions) || !array_key_exists('duration', $conditions)) {
            throw CouldNotGetLoanOffer::normalizationFailed(self::getName());
        }

        return [
            self::KEY_INTEREST_RATE => $conditions['zinsen'],
            self::KEY_MONTHS => $conditions['duration'],
        ];
    }
}
