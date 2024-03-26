<?php

namespace Fize\Provider\Region\Tests\Updater;

use PHPUnit\Framework\TestCase;

class TestMCA extends TestCase
{

    public function testUpdate()
    {
        MCA::update();
        self::assertTrue(true);
    }
}
