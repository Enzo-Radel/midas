<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Páginas Inertia renderizam @vite; os testes não dependem do build do frontend.
        $this->withoutVite();
    }
}
