<?php


use Fize\Provider\Region\RegionFactory;
use Fize\Provider\Region\RegionHandler;
use PHPUnit\Framework\TestCase;

class TestRegionFactory extends TestCase
{

    public function testCreate()
    {
        $factory = new RegionFactory();
        $local = $factory->create(RegionHandler::LOCAL);
        $items = $local->get(350213);
        var_dump($items);
        self::assertIsArray($items);
    }
}
