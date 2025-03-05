<?php

namespace Nikolai\HexletSlimExample;

class CarValidator
{
    public function validate(array $carData): array
    {
        $errors = [];

        if (strlen($carData['make']) < 4) {
            $errors['make'] = 'Make must be grater than 4 characters';
        }

        if (strlen($carData['model']) < 4) {
            $errors['model'] = 'Model must be grater than 4 characters';
        }

        return $errors;
    }
}