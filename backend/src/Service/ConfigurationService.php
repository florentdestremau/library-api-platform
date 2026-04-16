<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ConfigurationRepository;

class ConfigurationService
{
    private array $cache = [];

    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
    ) {
    }

    public function get(string $key, string $default = ''): string
    {
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->configurationRepository->getValue($key, $default);
        }

        return $this->cache[$key];
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, (string) $default);
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, (string) $default);
    }

    public function getLoanDurationDays(): int
    {
        return $this->getInt('loan_duration_days', 21);
    }

    public function getMaxRenewals(): int
    {
        return $this->getInt('max_renewals', 1);
    }

    public function getLateFeePerDay(): float
    {
        return $this->getFloat('late_fee_per_day', 0.10);
    }

    public function getReservationExpiryHours(): int
    {
        return $this->getInt('reservation_expiry_hours', 48);
    }

    public function getReminderDaysBefore(): int
    {
        return $this->getInt('reminder_days_before', 3);
    }

    public function getLibraryName(): string
    {
        return $this->get('library_name', 'Bibliothèque');
    }

    public function getLibraryEmail(): string
    {
        return $this->get('library_email', 'noreply@bibliotheque.fr');
    }
}
