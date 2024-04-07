<?php

namespace App\Contracts;

interface EmailNotifierInterface
{
    public function sendEmail(TicketInterface $ticket, string $transition, string $toState): void;
}
