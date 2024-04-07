<?php

namespace App\Service;

use App\Contracts\EmailValidatorInterface;

class EmailValidator implements EmailValidatorInterface
{
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
