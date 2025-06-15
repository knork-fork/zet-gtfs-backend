<?php
declare(strict_types=1);

namespace App\Tests\Common;

use PHPUnit\Framework\TestCase;

// use Prophecy\Prophet;

abstract class UnitTestCase extends TestCase
{
    // private Prophet $prophet;

    protected function setUp(): void
    {
        parent::setUp();
        // to-do: setup Prophecy extension
        // $this->prophet = new Prophet();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // $this->getProphet()->checkPredictions();
    }

    /*public function getProphet(): Prophet
    {
        return $this->prophet;
    }*/
}
