<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\CouldNotGetLoanOffer;
use PHPUnit\Framework\TestCase;

class CouldNotGetLoanOfferTest extends TestCase
{
    public function testNetworkError(): void
    {
        $exception = CouldNotGetLoanOffer::networkError('EvilCorp', new \Exception('Some exception'));

        $this->assertInstanceOf(CouldNotGetLoanOffer::class, $exception);
        $this->assertSame(
            'A request to "EvilCorp" endpoint failed because of a network error.',
            $exception->getMessage()
        );
    }

    public function testUnsuccessfulRequest(): void
    {
        $exception = CouldNotGetLoanOffer::unsuccessfulRequest('EvilCorp', 500);

        $this->assertInstanceOf(CouldNotGetLoanOffer::class, $exception);
        $this->assertSame('A request to "EvilCorp" endpoint responded with a 500.', $exception->getMessage());
    }

    public function testDecodingError(): void
    {
        $exception = CouldNotGetLoanOffer::decodingError('EvilCorp', new \Exception('Some exception'));

        $this->assertInstanceOf(CouldNotGetLoanOffer::class, $exception);
        $this->assertSame(
            'Failed to decode the body from "EvilCorp" endpoint to an array.',
            $exception->getMessage()
        );
    }

    public function testNormalizationFailed(): void
    {
        $exception = CouldNotGetLoanOffer::normalizationFailed('EvilCorp');

        $this->assertInstanceOf(CouldNotGetLoanOffer::class, $exception);
        $this->assertSame('Failed to normalize the data from "EvilCorp" endpoint.', $exception->getMessage());
    }
}
