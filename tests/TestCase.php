<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\UsesTemporarySigningKeys;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication {
        createApplication as bootApplication;
    }
    use UsesTemporarySigningKeys;

    public function createApplication()
    {
        $app = $this->bootApplication();
        $this->useTemporarySigningKeys($app);

        return $app;
    }
}
