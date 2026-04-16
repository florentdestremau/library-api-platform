<?php

declare(strict_types=1);

namespace App\Dto\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\DashboardStatsProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/stats/dashboard',
            security: "is_granted('ROLE_LIBRARIAN')",
            provider: DashboardStatsProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['stats:read']],
)]
class DashboardStatsOutput
{
    #[Groups(['stats:read'])]
    public int $activeLoans = 0;

    #[Groups(['stats:read'])]
    public int $overdueLoans = 0;

    #[Groups(['stats:read'])]
    public int $pendingReservations = 0;

    #[Groups(['stats:read'])]
    public int $activeMembers = 0;

    #[Groups(['stats:read'])]
    public int $expiredMembers = 0;

    #[Groups(['stats:read'])]
    public int $totalBooks = 0;

    /** @var array<array{book_title: string, book_id: string, borrow_count: int}> */
    #[Groups(['stats:read'])]
    public array $topBooks = [];
}
