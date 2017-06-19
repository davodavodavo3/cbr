<?php

if (!function_exists('currency')) {
    /**
     * Convert given number.
     *
     * @param float  $amount
     * @param string $from
     * @param string $to
     * @param bool   $format
     *
     * @return \Scorpion\Cbr\Currency|string
     */
    function currency($amount = null, $from = null, $to = null, $format = true)
    {
        if (is_null($amount)) {
            return app('cbr');
        }

        return app('cbr')->convert($amount, $from, $to, $format);
    }
}

if (!function_exists('currency_format')) {
    /**
     * Format given number.
     *
     * @param float  $amount
     * @param string $currency
     * @param bool   $include_symbol
     *
     * @return string
     */
    function currency_format($amount = null, $currency = null, $include_symbol = true)
    {
        return app('cbr')->format($amount, $currency, $include_symbol);
    }
}
