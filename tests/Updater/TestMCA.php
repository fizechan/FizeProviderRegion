<?php

namespace Updater;

use Fize\Provider\Region\Updater\MCA;
use PHPUnit\Framework\TestCase;

class TestMCA extends TestCase
{

    public function testUpdate()
    {
        MCA::update();
        self::assertTrue(true);
    }
}
