<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Input\LoanCreateInput;
use App\Entity\BookCopy;
use App\Entity\Member;
use App\Repository\BookCopyRepository;
use App\Repository\MemberRepository;
use App\Service\LoanService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoanCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly MemberRepository $memberRepository,
        private readonly BookCopyRepository $bookCopyRepository,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var LoanCreateInput $data */
        $member = $this->memberRepository->find($data->memberId);
        if ($member === null) {
            throw new NotFoundHttpException('Adhérent introuvable');
        }

        $bookCopy = $this->bookCopyRepository->find($data->bookCopyId);
        if ($bookCopy === null) {
            // Essayer par code-barres
            $bookCopy = $this->bookCopyRepository->findByBarcode($data->bookCopyId ?? '');
        }
        if ($bookCopy === null) {
            throw new NotFoundHttpException('Exemplaire introuvable');
        }

        $librarian = $this->security->getUser();

        return $this->loanService->createLoan(
            $member,
            $bookCopy,
            $librarian instanceof \App\Entity\User ? $librarian : null,
            $data->durationDays,
        );
    }
}
