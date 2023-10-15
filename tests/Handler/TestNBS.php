<?php

namespace Handler;

use Fize\Provider\Region\Handler\NBS;
use PHPUnit\Framework\TestCase;

class TestNBS extends TestCase
{

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

    public function testGetCountys()
    {
        $mca = new MCA();
        $items = $mca->getCountys(500200);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetFullName()
    {
        $mca = new MCA();
        $name = $mca->getFullName(110109, '-');
        var_dump($name);
        self::assertEquals('北京市-市辖区-门头沟区', $name);

        $name = $mca->getFullName(110109, '', 1);
        var_dump($name);
        self::assertEquals('北京市门头沟区', $name);

        $name = $mca->getFullName(442001, '', 1);
        var_dump($name);
        self::assertEquals('广东省中山市', $name);
    }

    public function testUpdate()
    {
        NBS::update();
        self::assertTrue(true);
    }
}
