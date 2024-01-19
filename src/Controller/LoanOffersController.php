<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoanOfferRequest;
use App\LoanOffer\LoanOfferHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LoanOffersController extends AbstractController
{
    #[Route('/loan-offers', name: 'app_offers')]
    public function offers(
        Request $request,
        ValidatorInterface $validator,
        LoanOfferHandler $loanOfferHandler
    ): Response {
        $loanOfferRequest = new LoanOfferRequest(
            amount: $request->query->get('amount', '') !== '' ? $request->query->getInt('amount') : null,
            months: $request->query->get('months', '') !== '' ? $request->query->getInt('months') : null
        );

        $validationErrors = [];
        foreach ($validator->validate($loanOfferRequest) as $constraintViolation) {
            $validationErrors[$constraintViolation->getPropertyPath()] = (string)$constraintViolation->getMessage();
        }

        $loanOffers = $loanOfferRequest->amount && !$validationErrors ?
            $loanOfferHandler->getOffers($loanOfferRequest->amount, $loanOfferRequest->months) :
            [];

        return $this->render('loan_offers/index.html.twig', [
            'errors' => $validationErrors,
            'amount' => $loanOfferRequest->amount,
            'months' => $loanOfferRequest->months,
            'offers' => $loanOffers
        ]);
    }
}
