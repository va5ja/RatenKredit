<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoanOffersFunctionalTest extends WebTestCase
{
    public function testSuccessfulResponseWithoutParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/loan-offers');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Loan Offers');
        $this->assertSelectorTextSame('h1', '💰 Installment Loan');
    }

    public function testSuccessfulResponseWithParameters(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/loan-offers?amount=1000');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('h1', '💰 Installment Loan');
        $this->assertSelectorExists('table');
        $this->assertCount(3, $crawler->filter('tr'));
    }

    public function testSuccessfulResponseWithParametersAndUnavailableProvider(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/loan-offers?amount=2000');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('h1', '💰 Installment Loan');
        $this->assertSelectorExists('table');
        $this->assertCount(3, $crawler->filter('tr'));
        $this->assertSelectorTextSame('td', 'Ing (currently unavailable)');
    }
}
