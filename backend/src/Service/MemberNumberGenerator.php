<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MemberRepository;

class MemberNumberGenerator
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
    ) {
    }

    public function generate(): string
    {
        $year = (int) date('Y');
        $sequence = $this->memberRepository->findNextMemberNumber($year);

        return sprintf('BIB-%d-%05d', $year, $sequence);
    }
}
