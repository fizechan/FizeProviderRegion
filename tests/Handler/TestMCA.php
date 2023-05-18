<?php

namespace Handler;

use Fize\Provider\Region\Handler\MCA;
use PHPUnit\Framework\TestCase;

class TestMCA extends TestCase
{

    public function testGet()
    {
        $mca = new MCA();
        $items = $mca->get(350213);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetProvinces()
    {
        $mca = new MCA();
        $items = $mca->getProvinces();
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetCitys()
    {
        $mca = new MCA();
        $items = $mca->getCitys(110000);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetAreas()
    {
        $mca = new MCA();
        $items = $mca->getAreas(110100);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testUpdate()
    {
        $mca = new MCA();
        $mca->update();
        self::assertTrue(true);
    }
}
