<?php

namespace handler;

use fize\provider\region\handler\Local;
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

    public function testGetProvince()
    {
        $local = new Local();
        $items = $local->getProvince();
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetCity()
    {
        $local = new Local();
        $items = $local->getCity(350000);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetArea()
    {
        $local = new Local();
        $items = $local->getArea(350200);
        var_dump($items);
        self::assertIsArray($items);
    }
}
