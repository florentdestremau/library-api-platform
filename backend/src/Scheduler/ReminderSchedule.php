<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Repository\LoanRepository;
use App\Service\ConfigurationService;
use App\Service\NotificationService;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule(name: 'reminders')]
class ReminderSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private readonly LoanRepository $loanRepository,
        private readonly NotificationService $notifications,
        private readonly ConfigurationService $config,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            RecurringMessage::cron('0 8 * * *', new SendRemindersMessage())
        );
    }
}
