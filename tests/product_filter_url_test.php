<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ProductFilterService;

function assertSameStrict($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

$parsed = ProductFilterService::parseFiltersFromUrl([
    'min_price' => '100',
    'max_price' => '500',
    'attr_10' => 'opt:2,opt:5,blue',
    'attr_11_min' => '5',
    'attr_11_max' => '20',
]);

assertSameStrict(100.0, $parsed['min_price'], 'min_price parse failed');
assertSameStrict(500.0, $parsed['max_price'], 'max_price parse failed');
assertSameStrict(['opt:2', 'opt:5', 'blue'], $parsed['attributes'][10], 'multi-select parse failed');
assertSameStrict(5.0, $parsed['attributes'][11]['min'], 'range min parse failed');
assertSameStrict(20.0, $parsed['attributes'][11]['max'], 'range max parse failed');

echo "product_filter_url_test passed\n";
