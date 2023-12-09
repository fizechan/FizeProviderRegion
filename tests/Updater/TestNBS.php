<?php

namespace Updater;

use PHPUnit\Framework\TestCase;

class TestNBS extends TestCase
{

    public function testUpdate()
    {
        NBS::update();
        self::assertTrue(true);
    }
}
