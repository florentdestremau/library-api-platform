<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Loan;
use App\Entity\Member;
use App\Entity\Notification;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $em,
        private readonly ConfigurationService $config,
    ) {
    }

    public function sendWelcome(Member $member): void
    {
        $this->send(
            $member,
            Notification::TYPE_WELCOME,
            'Bienvenue à la ' . $this->config->getLibraryName(),
            'email/welcome.html.twig',
            ['member' => $member],
        );
    }

    public function sendReminder(Member $member, Loan $loan): void
    {
        $this->send(
            $member,
            Notification::TYPE_REMINDER,
            'Rappel : retour prévu le ' . $loan->getDueDate()->format('d/m/Y'),
            'email/reminder.html.twig',
            ['member' => $member, 'loan' => $loan],
        );
    }

    public function sendOverdue(Member $member, Loan $loan): void
    {
        $lateFee = $loan->calculateLateFee($this->config->getLateFeePerDay());
        $this->send(
            $member,
            Notification::TYPE_OVERDUE,
            'Retard de retour - ' . $loan->getBookCopy()?->getBook()?->getTitle(),
            'email/overdue.html.twig',
            ['member' => $member, 'loan' => $loan, 'late_fee' => $lateFee],
        );
    }

    public function sendReservationReady(Member $member, Reservation $reservation): void
    {
        $this->send(
            $member,
            Notification::TYPE_RESERVATION_READY,
            'Votre réservation est disponible : ' . $reservation->getBook()?->getTitle(),
            'email/reservation_ready.html.twig',
            ['member' => $member, 'reservation' => $reservation],
        );
    }

    public function sendReservationCancelled(Member $member, Reservation $reservation): void
    {
        $this->send(
            $member,
            Notification::TYPE_RESERVATION_CANCELLED,
            'Réservation annulée : ' . $reservation->getBook()?->getTitle(),
            'email/reservation_cancelled.html.twig',
            ['member' => $member, 'reservation' => $reservation],
        );
    }

    public function sendRenewalConfirmation(Member $member, Loan $loan): void
    {
        $this->send(
            $member,
            Notification::TYPE_RENEWAL,
            'Renouvellement confirmé - retour le ' . $loan->getDueDate()->format('d/m/Y'),
            'email/renewal.html.twig',
            ['member' => $member, 'loan' => $loan],
        );
    }

    public function sendMembershipExpiry(Member $member): void
    {
        $this->send(
            $member,
            Notification::TYPE_MEMBERSHIP_EXPIRY,
            'Votre adhésion expire bientôt',
            'email/membership_expiry.html.twig',
            ['member' => $member],
        );
    }

    private function send(
        Member $member,
        string $type,
        string $subject,
        string $template,
        array $context = [],
    ): void {
        $notification = new Notification();
        $notification->setMember($member);
        $notification->setType($type);
        $notification->setSubject($subject);

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->config->getLibraryEmail(), $this->config->getLibraryName()))
                ->to(new Address($member->getEmail(), $member->getFirstName() . ' ' . $member->getLastName()))
                ->subject($subject)
                ->htmlTemplate($template)
                ->context(array_merge($context, [
                    'library_name' => $this->config->getLibraryName(),
                ]));

            $this->mailer->send($email);

            $notification->setStatus(Notification::STATUS_SENT);
            $notification->setSentAt(new \DateTimeImmutable());
            $notification->setBody($subject); // Simplifié
        } catch (\Throwable $e) {
            $notification->setStatus(Notification::STATUS_FAILED);
            $notification->setBody('Erreur : ' . $e->getMessage());
        }

        $this->em->persist($notification);
        $this->em->flush();
    }
}
