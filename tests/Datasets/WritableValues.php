<?php

declare(strict_types=1);

dataset('writable values', [
    'empty array' => [
        [],
    ],
    'array<int, null>' => [
        [null, null],
    ],
    'array<int, string>' => [
        ['foo', 'bar', 'baz'],
    ],
    'array<int, bool>' => [
        [true, false],
    ],
    'array<int, mixed>' => [
        ['foo', true, null]
    ],
    'array<string, null>' => [
        ['foo' => null, 'bar' => null],
    ],
    'array<string, string>' => [
        ['foo' => 'bar', 'baz' => 'qux'],
    ],
    'array<string, bool>' => [
        ['foo' => true, 'bar' => false],
    ],
    'array<string, mixed>' => [
        ['foo' => 'bar', 'baz' => true, 'qux' => null],
    ],
]);
