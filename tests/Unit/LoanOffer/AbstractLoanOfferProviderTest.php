<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanOffer;

use App\LoanOffer\Provider\AbstractLoanOfferProvider;
use App\Tests\Unit\Logger\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AbstractLoanOfferProviderTest extends TestCase
{
    private ?ResponseInterface $response = null;
    private ?HttpClientInterface $client = null;
    private ?CacheItemPoolInterface $cache = null;
    private ?LoggerInterface $logger = null;

    protected function setUp(): void
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->client = $this->createMock(HttpClientInterface::class);
        $this->client
            ->method('request')
            ->willReturn($this->response);

        $this->cache = new ArrayAdapter();
        $this->logger = new ArrayLogger();
    }

    protected function tearDown(): void
    {
        $this->response = null;
        $this->client = null;
        $this->cache = null;
        $this->logger = null;
    }

    public function testGetOfferWithNetworkError(): void
    {
        $this->response
            ->method('getStatusCode')
            ->willThrowException(new TransportException('Network error'));

        $loanOfferProvider = new class ($this->client, $this->cache, $this->logger) extends AbstractLoanOfferProvider {
            public static function getName(): string
            {
                return 'EvilCorp';
            }

            public function getClientResponse(int $amount, ?int $months): ResponseInterface
            {
                return $this->client->request('GET', 'https://evil-corp.com/api/loan-offer');
            }

            public function normalizeConditions(array $conditions): array
            {
                return $conditions;
            }
        };

        $offer = $loanOfferProvider->getOffer(1000);

        $this->assertCount(1, $this->logger->getLogs());
        $this->assertSame(
            'A request to "EvilCorp" endpoint failed because of a network error.',
            $this->logger->getLogs(0)['message']
        );
        $this->assertFalse($offer->requestSuccessful);
    }

    public function testGetOfferWithUnsuccessfulRequest(): void
    {
        $this->response
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);

        $loanOfferProvider = new class ($this->client, $this->cache, $this->logger) extends AbstractLoanOfferProvider {
            public static function getName(): string
            {
                return 'EvilCorp';
            }

            public function getClientResponse(int $amount, ?int $months): ResponseInterface
            {
                return $this->client->request('GET', 'https://evil-corp.com/api/loan-offer');
            }

            public function normalizeConditions(array $conditions): array
            {
                return $conditions;
            }
        };

        $offer = $loanOfferProvider->getOffer(1000);

        $this->assertCount(1, $this->logger->getLogs());
        $this->assertSame(
            'A request to "EvilCorp" endpoint responded with a 500.',
            $this->logger->getLogs(0)['message']
        );
        $this->assertFalse($offer->requestSuccessful);
    }

    public function testGetOfferWithContentNetworkError(): void
    {
        $this->response
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->response
            ->method('toArray')
            ->willThrowException(new TransportException('Network error'));

        $loanOfferProvider = new class ($this->client, $this->cache, $this->logger) extends AbstractLoanOfferProvider {
            public static function getName(): string
            {
                return 'EvilCorp';
            }

            public function getClientResponse(int $amount, ?int $months): ResponseInterface
            {
                return $this->client->request('GET', 'https://evil-corp.com/api/loan-offer');
            }

            public function normalizeConditions(array $conditions): array
            {
                return $conditions;
            }
        };

        $offer = $loanOfferProvider->getOffer(1000);

        $this->assertCount(1, $this->logger->getLogs());
        $this->assertSame(
            'A request to "EvilCorp" endpoint failed because of a network error.',
            $this->logger->getLogs(0)['message']
        );
        $this->assertFalse($offer->requestSuccessful);
    }

    public function testGetOfferWithContentDecodingError(): void
    {
        $this->response
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->response
            ->method('toArray')
            ->willThrowException(new JsonException());

        $loanOfferProvider = new class ($this->client, $this->cache, $this->logger) extends AbstractLoanOfferProvider {
            public static function getName(): string
            {
                return 'EvilCorp';
            }

            public function getClientResponse(int $amount, ?int $months): ResponseInterface
            {
                return $this->client->request('GET', 'https://evil-corp.com/api/loan-offer');
            }

            public function normalizeConditions(array $conditions): array
            {
                return $conditions;
            }
        };

        $offer = $loanOfferProvider->getOffer(1000);

        $this->assertCount(1, $this->logger->getLogs());
        $this->assertSame(
            'Failed to decode the body from "EvilCorp" endpoint to an array.',
            $this->logger->getLogs(0)['message']
        );
        $this->assertFalse($offer->requestSuccessful);
    }

    public function testGetOfferCaching(): void
    {
        $this->response
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->response
            ->method('toArray')
            ->willReturn(['interest_rate' => 1.5, 'months' => 12]);

        $loanOfferProvider = new class ($this->client, $this->cache, $this->logger) extends AbstractLoanOfferProvider {
            public static function getName(): string
            {
                return 'EvilCorp';
            }

            public function getClientResponse(int $amount, ?int $months): ResponseInterface
            {
                return $this->client->request('GET', 'https://evil-corp.com/api/loan-offer');
            }

            public function normalizeConditions(array $conditions): array
            {
                return $conditions;
            }
        };

        $offer = $loanOfferProvider->getOffer(1000);

        $this->assertCount(0, $this->logger->getLogs());
        $this->assertCount(1, $this->cache->getValues());
        $this->assertTrue($offer->requestSuccessful);
    }
}
