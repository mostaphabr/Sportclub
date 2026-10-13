<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests never read or delete real uploaded images:
        // a fake "public" disk and an empty temporary legacy folder.
        Storage::fake('public');
        config(['sportclub.legacy_uploads_path' => storage_path('framework/testing/no-legacy-uploads')]);
    }
}
