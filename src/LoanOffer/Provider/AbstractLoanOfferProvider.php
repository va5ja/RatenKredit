<?php

declare(strict_types=1);

namespace App\LoanOffer\Provider;

use App\Dto\LoanOfferResponse;
use App\Exception\CouldNotGetLoanOffer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractLoanOfferProvider implements LoanOfferProviderInterface
{
    protected const KEY_INTEREST_RATE = 'interest_rate';
    protected const KEY_MONTHS = 'months';
    protected const PREFIX_CACHE_ITEM = 'loan_offer_';

    public function __construct(
        protected string $accessKey,
        protected HttpClientInterface $client,
        protected CacheItemPoolInterface $cache,
        protected LoggerInterface $logger
    ) {
    }

    public function getOffer(int $amount, ?int $months = null): LoanOfferResponse
    {
        $cacheItemKey = self::PREFIX_CACHE_ITEM . md5(sprintf('%s_%d_%d', static::getName(), $amount, $months));
        $cacheItem = $this->cache->getItem($cacheItemKey);

        if ($cacheItem->isHit()) {
            $conditions = $cacheItem->get();
        } else {
            try {
                $conditions = $this->extractConditions($this->getClientResponse($amount, $months));
                $conditions = $this->normalizeConditions($conditions);
            } catch (CouldNotGetLoanOffer $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);

                return new LoanOfferResponse(providerName: static::getName(), requestSuccessful: false);
            }

            $cacheItem->set($conditions)->expiresAfter(30);
            $this->cache->save($cacheItem);
        }

        return new LoanOfferResponse(
            providerName: static::getName(),
            requestSuccessful: true,
            interestRate: $conditions[self::KEY_INTEREST_RATE],
            months: $conditions[self::KEY_MONTHS]
        );
    }

    protected function extractConditions(ResponseInterface $response): array
    {
        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw CouldNotGetLoanOffer::networkError(static::getName(), $e);
        }

        if ($statusCode !== Response::HTTP_OK) {
            throw CouldNotGetLoanOffer::unsuccessfulRequest(static::getName(), $response->getStatusCode());
        }

        try {
            $data = $response->toArray(false);
        } catch (TransportExceptionInterface $e) {
            throw CouldNotGetLoanOffer::networkError(static::getName(), $e);
        } catch (DecodingExceptionInterface $e) {
            throw CouldNotGetLoanOffer::decodingError(static::getName(), $e);
        }

        return $data;
    }

    abstract protected function getClientResponse(int $amount, ?int $months): ResponseInterface;

    /**
     * @return array{interest_rate: float, months: integer}
     * @throws CouldNotGetLoanOffer
     */
    abstract protected function normalizeConditions(array $conditions): array;
}
