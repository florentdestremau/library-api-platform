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
        $memberId = $data->memberId ?? '';
        $isMemberUuid = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $memberId);

        if ($isMemberUuid) {
            $member = $this->memberRepository->find($memberId);
        } else {
            // Chercher par numéro d'adhérent (BIB-YYYY-XXXXX)
            $member = $this->memberRepository->findOneBy(['memberNumber' => $memberId]);
        }

        if ($member === null) {
            throw new NotFoundHttpException('Adhérent introuvable');
        }

        // Tenter d'abord par code-barres, puis par UUID
        $bookCopyId = $data->bookCopyId ?? '';
        $isUuid = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $bookCopyId);

        if ($isUuid) {
            $bookCopy = $this->bookCopyRepository->find($bookCopyId);
        } else {
            $bookCopy = $this->bookCopyRepository->findByBarcode($bookCopyId);
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
