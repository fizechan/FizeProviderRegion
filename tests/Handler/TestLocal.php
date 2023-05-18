<?php

namespace Handler;

use Fize\Provider\Region\Handler\Local;
use PHPUnit\Framework\TestCase;

class TestLocal extends TestCase
{

    public function testGet()
    {
        $local = new Local();
        $items = $local->get(350213);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetProvinces()
    {
        $local = new Local();
        $items = $local->getProvinces();
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetCitys()
    {
        $local = new Local();
        $items = $local->getCitys(350000);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetAreas()
    {
        $local = new Local();
        $items = $local->getAreas(350200);
        var_dump($items);
        self::assertIsArray($items);
    }
}
