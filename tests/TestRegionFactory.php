<?php

namespace Fize\Provider\Region\Tests;

use Fize\Provider\Region\RegionFactory;
use Fize\Provider\Region\RegionHandler;
use PHPUnit\Framework\TestCase;

class TestRegionFactory extends TestCase
{

    public function testCreate()
    {
        $factory = new RegionFactory();
        $mca = $factory->create(RegionHandler::MCA);
        $items = $mca->get(350213);
        var_dump($items);
        self::assertIsArray($items);
    }
}
