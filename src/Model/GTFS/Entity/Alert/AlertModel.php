<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity\Alert;

final class AlertModel
{
    /**
     * @param AlertTimeRangeModel[]|null      $activePeriod
     * @param AlertEntitySelectorModel[]|null $informedEntity
     */
    public function __construct(
        public ?array $activePeriod,
        public ?array $informedEntity,
        public ?string $cause,
        public ?string $effect,
        public ?AlertTranslationModel $url,
        public ?AlertTranslationModel $headerText,
        public ?AlertTranslationModel $descriptionText
    ) {
    }
}
