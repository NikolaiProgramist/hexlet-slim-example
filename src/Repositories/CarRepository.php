<?php

namespace Nikolai\HexletSlimExample\Repositories;

use PDO;
use Nikolai\HexletSlimExample\Car;

class CarRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getEntities(): array
    {
        $sql = "SELECT * FROM cars";
        $stmt = $this->conn->query($sql);
        $cars = [];

        while ($row = $stmt->fetch()) {
            $car = Car::fromArray([$row['make'], $row['model']]);
            $car->setId($row['id']);
            $cars[] = $car;
        }

        return $cars;
    }

    public function find(int $id): ?Car
    {
        $sql = "SELECT * FROM cars WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch()) {
            $car = Car::fromArray([$row['make'], $row['model']]);
            $car->setId($row['id']);
            return $car;
        }

        return null;
    }

    public function save(Car $car): void
    {
        if ($car->exists()) {
            $this->update($car);
        } else {
            $this->create($car);
        }
    }

    public function update(Car $car): void
    {
        $sql = "UPDATE cars SET make = :make, model = :model WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':make' => $car->getMake(), ':model' => $car->getModel(), ':id' => $car->getId()]);
    }

    public function create(Car $car): void
    {
        $sql = "INSERT INTO cars (make, model) VALUES (:make, :model)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':make' => $car->getMake(), ':model' => $car->getModel()]);
        $id = (int) $this->conn->lastInsertId();
        $car->setId($id);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM cars WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
    }
}