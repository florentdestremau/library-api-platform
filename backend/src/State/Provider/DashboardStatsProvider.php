<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Output\DashboardStatsOutput;
use App\Entity\Member;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Repository\MemberRepository;
use App\Repository\ReservationRepository;

class DashboardStatsProvider implements ProviderInterface
{
    public function __construct(
        private readonly LoanRepository $loanRepository,
        private readonly MemberRepository $memberRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly BookRepository $bookRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $stats = new DashboardStatsOutput();
        $stats->activeLoans = $this->loanRepository->countActive();
        $stats->overdueLoans = $this->loanRepository->countOverdue();
        $stats->pendingReservations = $this->reservationRepository->countPending();
        $stats->activeMembers = $this->memberRepository->count(['status' => Member::STATUS_ACTIVE]);
        $stats->expiredMembers = $this->memberRepository->count(['status' => Member::STATUS_EXPIRED]);
        $stats->totalBooks = $this->bookRepository->count([]);
        $stats->topBooks = $this->loanRepository->findMostBorrowed(10);

        return $stats;
    }
}
