<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Repository\LoanRepository;
use App\Repository\MemberRepository;
use App\Repository\ReservationRepository;
use App\Service\ConfigurationService;
use App\Service\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendRemindersHandler
{
    public function __construct(
        private readonly LoanRepository $loanRepository,
        private readonly MemberRepository $memberRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly NotificationService $notifications,
        private readonly ConfigurationService $config,
    ) {
    }

    public function __invoke(SendRemindersMessage $message): void
    {
        $reminderDays = $this->config->getReminderDaysBefore();

        // Rappels avant échéance
        $dueSoon = $this->loanRepository->findDueSoon($reminderDays);
        foreach ($dueSoon as $loan) {
            $member = $loan->getMember();
            if ($member !== null) {
                $this->notifications->sendReminder($member, $loan);
            }
        }

        // Relances pour les retards
        $overdueLoans = $this->loanRepository->findOverdue();
        foreach ($overdueLoans as $loan) {
            $member = $loan->getMember();
            if ($member !== null) {
                $this->notifications->sendOverdue($member, $loan);
            }
        }

        // Expiration des réservations non récupérées
        $expiredReservations = $this->reservationRepository->findExpiredNotified();
        foreach ($expiredReservations as $reservation) {
            $reservation->setStatus(\App\Entity\Reservation::STATUS_EXPIRED);
            $member = $reservation->getMember();
            if ($member !== null) {
                $this->notifications->sendReservationCancelled($member, $reservation);
            }
        }

        // Rappels expiration adhésion (dans 30 jours)
        $expiringDate = new \DateTimeImmutable('+30 days');
        $expiringMembers = $this->memberRepository->createQueryBuilder('m')
            ->where('m.membershipExpiry <= :expiry')
            ->andWhere('m.membershipExpiry >= :now')
            ->andWhere('m.status = :status')
            ->setParameter('expiry', $expiringDate)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', \App\Entity\Member::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();

        foreach ($expiringMembers as $member) {
            $this->notifications->sendMembershipExpiry($member);
        }
    }
}
