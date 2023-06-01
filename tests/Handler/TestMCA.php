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
        $items = $mca->getCitys(500000);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetAreas()
    {
        $mca = new MCA();
        $items = $mca->getAreas(500200);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testUpdate()
    {
        MCA::update();
        self::assertTrue(true);
    }
}
