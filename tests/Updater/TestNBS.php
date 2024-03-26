<?php

namespace Fize\Provider\Region\Tests\Updater;

use PHPUnit\Framework\TestCase;

class TestNBS extends TestCase
{

    public function testUpdate()
    {
        NBS::update();
        self::assertTrue(true);
    }
}
