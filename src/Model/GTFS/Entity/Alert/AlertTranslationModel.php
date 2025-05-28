<?php
declare(strict_types=1);

namespace App\Model\GTFS\Entity\Alert;

final class AlertTranslationModel
{
    public function __construct(
        public string $text,
        public ?string $language,
    ) {
    }
}
