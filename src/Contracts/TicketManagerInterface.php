<?php

namespace App\Contracts;

interface TicketManagerInterface
{
    public function applyStateTransition(TicketInterface $ticket, string $transition): TicketInterface;

    public function updateEmailAddress(int $id, string $emailAddress): void;

    public function getInitialState(): string;

    public function getFirstRegisteredTicket(): TicketInterface;

    public function updateState(int $id, string $state): void;
}
