<?php

namespace Nikolai\HexletSlimExample;

class Car
{
    private int $id;
    private string $make;
    private string $model;

    public function __construct(string $make = '', string $model = '')
    {
        $this->make = $make;
        $this->model = $model;
    }

    public static function fromArray(array $carProperties): Car
    {
        return new Car($carProperties[0], $carProperties[1]);
    }

    public function exists(): bool
    {
        return isset($this->id);
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
}