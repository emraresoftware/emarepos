<?php

if (! function_exists('formatCurrency')) {
    /**
     * Format an amount as Turkish Lira currency string.
     */
    function formatCurrency(float|int|null $amount, int $decimals = 2): string
    {
        return number_format($amount ?? 0, $decimals, ',', '.') . ' ₺';
    }
}
