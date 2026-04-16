<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MyReservationsProvider implements ProviderInterface
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || $user->getMember() === null) {
            throw new AccessDeniedHttpException('Vous devez être connecté en tant qu\'adhérent');
        }

        return $this->reservationRepository->findBy(
            ['member' => $user->getMember()],
            ['createdAt' => 'DESC'],
        );
    }
}
