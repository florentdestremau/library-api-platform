<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

class ReservationCreateInput
{
    #[Assert\NotBlank]
    public ?string $bookId = null;
}
