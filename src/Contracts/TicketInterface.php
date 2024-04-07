<?php

namespace App\Contracts;

interface TicketInterface
{
    public function getId(): int;

    public function getState(): ?string;

    public function setState(string $state): static;

    public function getEmail(): ?string;

    public function setEmail(string $email): static;
}
