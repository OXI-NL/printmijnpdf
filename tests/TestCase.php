<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Geen echt verkeer naar buiten (o.a. api.mollie.com); tests die HTTP
        // nodig hebben, faken het zelf met Http::fake()
        Http::preventStrayRequests();

        // Geüploade PDF's niet in de echte storage/app achterlaten
        Storage::fake('local');
    }
}
