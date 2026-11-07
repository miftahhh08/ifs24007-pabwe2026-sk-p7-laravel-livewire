<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Layout memakai @vite; tes tidak boleh bergantung pada hasil `npm run build`.
        $this->withoutVite();
    }
}
