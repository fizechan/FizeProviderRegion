<?php

namespace Handler;

use Fize\Provider\Region\Handler\NBS;
use PHPUnit\Framework\TestCase;

class TestNBS extends TestCase
{

    public function testGet()
    {
        $mca = new NBS();
        $items = $mca->get(11);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetProvinces()
    {
        $nbs = new NBS();
        $items = $nbs->getProvinces();
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetCitys()
    {
        $nbs = new NBS();
        $items = $nbs->getCitys(50);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetCountys()
    {
        $nbs = new NBS();
        $items = $nbs->getCountys(5002);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetFullName()
    {
        $nbs = new NBS();

        $name = $nbs->getFullName(350521111219);
        var_dump($name);
        self::assertEquals('福建省泉州市惠安县东桥镇莲塘村委会', $name);

        $name = $nbs->getFullName(110109, '-');
        var_dump($name);
        self::assertEquals('北京市-市辖区-门头沟区', $name);

        $name = $nbs->getFullName(110109, '', 1);
        var_dump($name);
        self::assertEquals('北京市门头沟区', $name);

        $name = $nbs->getFullName(442000, '', 1);  # 直筒子市
        var_dump($name);
        self::assertEquals('广东省中山市', $name);

        $name = $nbs->getFullName(350581, '-', 1);
        var_dump($name);
        self::assertEquals('福建省-泉州市-石狮市', $name);

        $name = $nbs->getFullName(350581, '-', 2);  # 去除中间市
        var_dump($name);
        self::assertEquals('福建省-石狮市', $name);

        $name = $nbs->getFullName(350581100207, '-', 2);
        var_dump($name);
        self::assertEquals('福建省-石狮市-灵秀镇-港塘村委会', $name);
    }
}
