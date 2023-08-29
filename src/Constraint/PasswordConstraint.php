<?php

namespace App\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class PasswordConstraint extends Constraint
{
    public string $message = 'Your password is weak. The password must contain at least 12 characters, including uppercase and lowercase letters, numbers and special characters';
    /*
     * need min. one uppercase letter
     * need min. one lowercase letter
     * need min. one number
     * need min. one special char
     * min 12 chars total
     */
    public string $regex = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{12,}$/';

}