<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Member;
use App\Service\MemberNumberGenerator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Member::class)]
class MemberNumberListener
{
    public function __construct(
        private readonly MemberNumberGenerator $generator,
    ) {
    }

    public function prePersist(Member $member, PrePersistEventArgs $args): void
    {
        if ($member->getMemberNumber() === '') {
            $member->setMemberNumber($this->generator->generate());
        }
    }
}
