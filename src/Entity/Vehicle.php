<?php
declare(strict_types=1);

namespace App\Entity;

use App\System\Database\Entity;

final class Vehicle extends Entity
{
    public int $route_id;
    public float $position_lat;
    public float $position_long;
    public float $rotation_deg;
}
