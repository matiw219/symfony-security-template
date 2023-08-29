<?php

namespace App\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordConstraintValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($constraint instanceof PasswordConstraint) {
            /*
             * need min. one uppercase letter
             * need min. one lowercase letter
             * need min. one number
             * need min. one special char
             * min 12 chars total
             */
            if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{12,}$/', $value)) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        }
    }
}