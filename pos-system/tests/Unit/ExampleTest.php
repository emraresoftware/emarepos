<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Para format helper Türk Lirası formatı üretir.
     */
    public function test_format_currency_returns_turkish_lira_string(): void
    {
        require_once __DIR__ . '/../../app/helpers.php';

        $this->assertSame('1.234,50 ₺', formatCurrency(1234.5));
    }
}
