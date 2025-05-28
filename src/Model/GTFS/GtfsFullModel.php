<?php
declare(strict_types=1);

namespace App\Model\GTFS;

use App\Model\GTFS\Entity\EntityModel;
use App\Model\GTFS\Header\HeaderModel;

final class GtfsFullModel
{
    /**
     * @param EntityModel[] $entities
     */
    public function __construct(
        public HeaderModel $headerModel,
        public array $entities = []
    ) {
    }
}
