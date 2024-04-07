<?php

namespace App\Contracts;

interface EmailValidatorInterface
{
    public function isValidEmail(string $email): bool;
}
