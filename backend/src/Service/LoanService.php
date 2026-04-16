<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Entity\Member;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class LoanService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoanRepository $loanRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly ConfigurationService $config,
        private readonly NotificationService $notifications,
    ) {
    }

    public function createLoan(Member $member, BookCopy $bookCopy, ?User $librarian = null, ?int $durationDays = null): Loan
    {
        // RG-E1 : vérifier statut adhérent
        if (!$member->canBorrow()) {
            throw new UnprocessableEntityHttpException(
                sprintf('L\'adhérent %s ne peut pas emprunter (statut: %s)', $member->getMemberNumber(), $member->getStatus())
            );
        }

        // RG-E2 : vérifier quota
        $activeLoans = $this->loanRepository->findActiveLoansByMember($member);
        if (count($activeLoans) >= $member->getMaxLoans()) {
            throw new UnprocessableEntityHttpException(
                sprintf('L\'adhérent a atteint son quota de %d emprunts simultanés', $member->getMaxLoans())
            );
        }

        // RG-E3 : vérifier disponibilité exemplaire
        if (!$bookCopy->isAvailableForLoan()) {
            throw new UnprocessableEntityHttpException(
                sprintf('L\'exemplaire %s n\'est pas disponible (statut: %s)', $bookCopy->getBarcode(), $bookCopy->getStatus())
            );
        }

        $duration = $durationDays ?? $this->config->getLoanDurationDays();
        $dueDate = new \DateTimeImmutable("+{$duration} days");

        // RG-E4 : date de retour dans le futur
        if ($dueDate <= new \DateTimeImmutable()) {
            throw new BadRequestHttpException('La date de retour ne peut pas être dans le passé');
        }

        $loan = new Loan();
        $loan->setMember($member);
        $loan->setBookCopy($bookCopy);
        $loan->setLibrarian($librarian);
        $loan->setDueDate($dueDate);

        // Mettre à jour le statut de l'exemplaire
        $bookCopy->setStatus(BookCopy::STATUS_BORROWED);

        // Annuler une éventuelle réservation de cet adhérent pour ce livre
        $book = $bookCopy->getBook();
        if ($book !== null) {
            $reservation = $this->reservationRepository->findOneBy([
                'book' => $book,
                'member' => $member,
                'status' => Reservation::STATUS_NOTIFIED,
            ]);
            if ($reservation !== null) {
                $reservation->setStatus(Reservation::STATUS_FULFILLED);
            }
        }

        $this->em->persist($loan);
        $this->em->persist($bookCopy);
        $this->em->flush();

        return $loan;
    }

    public function returnLoan(Loan $loan, string $condition, ?User $librarian = null): Loan
    {
        if ($loan->isReturned()) {
            throw new BadRequestHttpException('Cet emprunt a déjà été rendu');
        }

        $loan->setReturnedAt(new \DateTimeImmutable());
        $loan->setReturnCondition($condition);
        $loan->setLibrarian($librarian);

        // Calculer pénalités si retard
        if ($loan->isOverdue()) {
            $fee = $loan->calculateLateFee($this->config->getLateFeePerDay());
            $loan->setLateFee(number_format($fee, 2, '.', ''));
        }

        $bookCopy = $loan->getBookCopy();
        if ($bookCopy !== null) {
            // Mettre à jour le statut de l'exemplaire
            if ($condition === Loan::RETURN_CONDITION_LOST) {
                $bookCopy->setStatus(BookCopy::STATUS_LOST);
                // Suspendre l'adhérent
                $loan->getMember()?->setStatus(Member::STATUS_SUSPENDED);
            } elseif ($condition === Loan::RETURN_CONDITION_DAMAGED) {
                $bookCopy->setStatus(BookCopy::STATUS_REPAIR);
            } else {
                $bookCopy->setStatus(BookCopy::STATUS_AVAILABLE);
            }

            $this->em->persist($bookCopy);

            // Notifier la prochaine réservation si disponible
            if ($bookCopy->getStatus() === BookCopy::STATUS_AVAILABLE) {
                $book = $bookCopy->getBook();
                if ($book !== null) {
                    $nextReservation = $this->reservationRepository->findNextInQueue($book);
                    if ($nextReservation !== null) {
                        $expiryHours = $this->config->getReservationExpiryHours();
                        $nextReservation->setStatus(Reservation::STATUS_NOTIFIED);
                        $nextReservation->setNotifiedAt(new \DateTimeImmutable());
                        $nextReservation->setExpiresAt(new \DateTimeImmutable("+{$expiryHours} hours"));
                        $this->em->persist($nextReservation);
                        $this->notifications->sendReservationReady($nextReservation->getMember(), $nextReservation);
                    }
                }
            }
        }

        $this->em->persist($loan);
        $this->em->flush();

        return $loan;
    }

    public function renewLoan(Loan $loan, Member $requestingMember): Loan
    {
        if ($loan->isReturned()) {
            throw new BadRequestHttpException('Impossible de renouveler un emprunt déjà rendu');
        }

        // RG-E6 : vérifier nombre de renouvellements
        $maxRenewals = $this->config->getMaxRenewals();
        if ($loan->getRenewedCount() >= $maxRenewals) {
            throw new UnprocessableEntityHttpException(
                sprintf('Nombre maximum de renouvellements atteint (%d)', $maxRenewals)
            );
        }

        // RG-E5 : vérifier réservations en attente
        $book = $loan->getBookCopy()?->getBook();
        if ($book !== null) {
            $pendingReservations = $this->reservationRepository->findActiveByBook($book);
            if (!empty($pendingReservations)) {
                throw new UnprocessableEntityHttpException(
                    'Impossible de renouveler : une réservation est en attente pour cet ouvrage'
                );
            }
        }

        $duration = $this->config->getLoanDurationDays();
        $loan->setDueDate(new \DateTimeImmutable("+{$duration} days"));
        $loan->setRenewedCount($loan->getRenewedCount() + 1);

        $this->em->persist($loan);
        $this->em->flush();

        $member = $loan->getMember();
        if ($member !== null) {
            $this->notifications->sendRenewalConfirmation($member, $loan);
        }

        return $loan;
    }
}
