<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Input\ReservationCreateInput;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Service\ReservationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReservationCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly BookRepository $bookRepository,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var ReservationCreateInput $data */
        $book = $this->bookRepository->find($data->bookId);
        if ($book === null) {
            throw new NotFoundHttpException('Ouvrage introuvable');
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User || $currentUser->getMember() === null) {
            throw new AccessDeniedHttpException('Vous devez être connecté en tant qu\'adhérent');
        }

        return $this->reservationService->createReservation($currentUser->getMember(), $book);
    }
}
