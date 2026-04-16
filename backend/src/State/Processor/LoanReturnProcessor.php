<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Input\LoanReturnInput;
use App\Entity\Loan;
use App\Repository\LoanRepository;
use App\Service\LoanService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoanReturnProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly LoanRepository $loanRepository,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var LoanReturnInput $data */
        $loan = $this->loanRepository->find($uriVariables['id']);
        if ($loan === null) {
            throw new NotFoundHttpException('Emprunt introuvable');
        }

        $librarian = $this->security->getUser();

        if ($data->notes !== null) {
            $loan->setNotes($data->notes);
        }

        return $this->loanService->returnLoan(
            $loan,
            $data->condition,
            $librarian instanceof \App\Entity\User ? $librarian : null,
        );
    }
}
