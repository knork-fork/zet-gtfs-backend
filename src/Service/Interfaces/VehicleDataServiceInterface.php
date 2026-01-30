<?php
declare(strict_types=1);

namespace App\Service\Interfaces;

use App\Entity\Vehicle;

interface VehicleDataServiceInterface
{
    /**
     * Load vehicles from GTFS cache and parse minimized vehicle data to database
     */
    public function saveVehicleDataToDb(): void;

    /**
     * @return Vehicle[]
     */
    public function getVehicleDataFromDb(): array;
}
