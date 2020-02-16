<?php


use fize\provider\region\Region;
use PHPUnit\Framework\TestCase;

class TestRegion extends TestCase
{

    public function testGetInstance()
    {
        $local = Region::getInstance('Local');
        $items = $local->get(350213);
        var_dump($items);
        self::assertIsArray($items);
    }
}
