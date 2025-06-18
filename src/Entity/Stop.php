<?php
declare(strict_types=1);

namespace App\Entity;

use App\System\Database\Entity;

final class Stop extends Entity
{
    public string $stop_id;
    public string $stop_name;
    public float $stop_lat;
    public float $stop_lon;
    public ?string $parent_station = null;
}
