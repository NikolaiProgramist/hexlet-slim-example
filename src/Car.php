<?php

namespace Nikolai\HexletSlimExample;

class Car
{
    private int $id;
    private string $make;
    private string $model;

    public static function fromArray(array $carProperties): Car
    {
        $car = new Car();
        $car->setMake($carProperties['make']);
        $car->setModel($carProperties['model']);

        return $car;
    }

    public function exists(): bool
    {
        return $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMake(): string
    {
        return $this->make;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setMake(string $make): void
    {
        $this->make = $make;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }
}