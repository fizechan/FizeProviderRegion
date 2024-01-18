<?php

namespace Updater;

use Fize\Provider\Region\Updater\NBS;
use PHPUnit\Framework\TestCase;

class TestNBS extends TestCase
{

    public function testUpdate()
    {
        NBS::update();
        self::assertTrue(true);
    }
}
