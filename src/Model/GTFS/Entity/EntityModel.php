<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity;

use App\Model\GTFS\Entity\Alert\AlertModel;
use App\Model\GTFS\Entity\TripUpdate\TripUpdateModel;
use App\Model\GTFS\Entity\VehiclePosition\VehiclePositionModel;

final class EntityModel
{
    public function __construct(
        public string $id,
        public ?TripUpdateModel $tripUpdate = null,
        public ?VehiclePositionModel $vehiclePosition = null,
        public ?AlertModel $alert = null
    ) {
    }
}
