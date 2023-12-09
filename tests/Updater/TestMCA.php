<?php

namespace Updater;

use PHPUnit\Framework\TestCase;

class TestMCA extends TestCase
{

    public function testUpdate()
    {
        MCA::update();
        self::assertTrue(true);
    }
}
