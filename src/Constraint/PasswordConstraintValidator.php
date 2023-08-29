<?php

namespace App\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordConstraintValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($constraint instanceof PasswordConstraint) {
            if (!preg_match($constraint->regex, $value)) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        }
    }
}