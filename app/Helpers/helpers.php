<?php

if (! function_exists('formatRupiah')) {
    function formatRupiah($number): string
    {
        return 'Rp' . number_format((int) $number, 0, ',', '.');
    }
}
