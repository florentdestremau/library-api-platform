<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

class LoanCreateInput
{
    #[Assert\NotBlank]
    public ?string $bookCopyId = null;

    #[Assert\NotBlank]
    public ?string $memberId = null;

    #[Assert\Positive]
    public ?int $durationDays = null;
}
