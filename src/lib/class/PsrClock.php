<?php
use Psr\Clock\ClockInterface;

class PsrClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }
}