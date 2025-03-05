<?php

namespace Nikolai\HexletSlimExample;

class Validator
{
    public function validate(array $userData): array
    {
        $errors = [];

        if (strlen($userData['nickname']) < 4) {
            $errors['nickname'] = 'Nickname must be grater than 4 characters';
        }

        if (strlen($userData['email']) < 4 && str_contains($userData['email'], '@')) {
            $errors['email'] = 'Uncorrected email';
        }

        return $errors;
    }
}