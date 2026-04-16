<?php

declare(strict_types=1);

namespace App\Dto\Input;

use App\Entity\Loan;
use Symfony\Component\Validator\Constraints as Assert;

class LoanReturnInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [
        Loan::RETURN_CONDITION_GOOD,
        Loan::RETURN_CONDITION_DAMAGED,
        Loan::RETURN_CONDITION_LOST,
    ])]
    public string $condition = Loan::RETURN_CONDITION_GOOD;

    public ?string $notes = null;
}
