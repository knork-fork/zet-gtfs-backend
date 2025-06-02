<?php
declare(strict_types=1);

namespace App\Entity;

use App\System\Database\Entity;

final class StopTime extends Entity
{
    public string $stop_id;
    public string $trip_id;
    public string $arrival_time;
    public int $stop_sequence;

    public function __construct()
    {
        parent::__construct(tableName: 'stop_times');
    }
}
