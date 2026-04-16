<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\LoanRepository;
use App\Service\LoanService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoanRenewProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly LoanRepository $loanRepository,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $loan = $this->loanRepository->find($uriVariables['id']);
        if ($loan === null) {
            throw new NotFoundHttpException('Emprunt introuvable');
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException();
        }

        // Un membre ne peut renouveler que ses propres emprunts
        if ($currentUser->getRole() === User::ROLE_MEMBER) {
            if ($currentUser->getMember() !== $loan->getMember()) {
                throw new AccessDeniedHttpException('Vous ne pouvez renouveler que vos propres emprunts');
            }
        }

        $member = $loan->getMember();
        if ($member === null) {
            throw new \LogicException('Emprunt sans adhérent');
        }

        return $this->loanService->renewLoan($loan, $member);
    }
}
