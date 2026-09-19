<?php

namespace App\Services;

use App\Enums\TicketPriority;
use Carbon\CarbonImmutable;

class TicketSlaService
{
    public function dueAt(TicketPriority $priority, ?CarbonImmutable $from = null): CarbonImmutable
    {
        return ($from ?? CarbonImmutable::now())->addHours($priority->slaHours());
    }

    public function isBreached(?\DateTimeInterface $dueAt, ?\DateTimeInterface $resolvedAt = null): bool
    {
        if (! $dueAt) {
            return false;
        }

        $comparison = $resolvedAt ? CarbonImmutable::instance($resolvedAt) : CarbonImmutable::now();

        return $comparison->greaterThan(CarbonImmutable::instance($dueAt));
    }
}
