<?php

declare(strict_types=1);

namespace App\Exception;

final class CouldNotGetLoanOffer extends \RuntimeException
{
    public static function networkError(string $providerName, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('A request to "%s" endpoint failed because of a network error.', $providerName),
            code: 0,
            previous: $previous
        );
    }

    public static function unsuccessfulRequest(string $providerName, int $statusCode): self
    {
        return new self(
            message: \sprintf('A request to "%s" endpoint responded with a %d.', $providerName, $statusCode)
        );
    }

    public static function decodingError(string $providerName, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('Failed to decode the body from "%s" endpoint to an array.', $providerName),
            code: 0,
            previous: $previous
        );
    }

    public static function normalizationFailed(string $providerName): self
    {
        return new self(
            message: \sprintf('Failed to normalize the data from "%s" endpoint.', $providerName)
        );
    }
}
