<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Member;
use App\Entity\Reservation;
use App\Repository\BookCopyRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ReservationRepository $reservationRepository,
        private readonly LoanRepository $loanRepository,
        private readonly BookCopyRepository $bookCopyRepository,
    ) {
    }

    public function createReservation(Member $member, Book $book): Reservation
    {
        // RG-R1 : vérifier qu'aucun exemplaire n'est disponible
        $availableCopies = $this->bookCopyRepository->findBy([
            'book' => $book,
            'status' => BookCopy::STATUS_AVAILABLE,
        ]);
        if (!empty($availableCopies)) {
            throw new UnprocessableEntityHttpException(
                'Impossible de réserver : un exemplaire est disponible'
            );
        }

        // RG-R2 : vérifier que l'adhérent n'a pas cet ouvrage en emprunt
        $activeLoans = $this->loanRepository->findActiveLoansByMember($member);
        foreach ($activeLoans as $loan) {
            if ($loan->getBookCopy()?->getBook() === $book) {
                throw new UnprocessableEntityHttpException(
                    'Vous avez déjà cet ouvrage en emprunt'
                );
            }
        }

        // RG-R3 : vérifier doublon de réservation
        if ($this->reservationRepository->hasActiveReservation($member, $book)) {
            throw new UnprocessableEntityHttpException(
                'Vous avez déjà une réservation en cours pour cet ouvrage'
            );
        }

        // RG-R4 : vérifier quota de réservations
        if ($member->getActiveReservationsCount() >= $member->getMaxReservations()) {
            throw new UnprocessableEntityHttpException(
                sprintf('Quota de réservations atteint (%d)', $member->getMaxReservations())
            );
        }

        // RG-R5 : position dans la file
        $queue = $this->reservationRepository->findActiveByBook($book);
        $position = count($queue) + 1;

        $reservation = new Reservation();
        $reservation->setMember($member);
        $reservation->setBook($book);
        $reservation->setQueuePosition($position);

        $this->em->persist($reservation);
        $this->em->flush();

        return $reservation;
    }
}
