<?php

/*
 Tests for the PHP base32 Damm check digit library. Dependency-free: run with
 `php php/tests/Damm32Test.php`.

 Copyright (c) 2014, Daniel Tillett
 SPDX-License-Identifier: BSD-2-Clause
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Damm32.php';

use Damm32\Damm32;

$failures = 0;

function check(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        $failures++;
    }
}

function withCheckDigit(string $code): string
{
    return $code . Damm32::checkDigit($code);
}

/* Alphabet is a bijection and excludes confusable characters. */
check(strlen(Damm32::ALPHABET) === Damm32::BASE, 'alphabet has 32 characters');
check(count(array_unique(str_split(Damm32::ALPHABET))) === Damm32::BASE, 'alphabet characters are distinct');
for ($i = 0; $i < Damm32::BASE; $i++) {
    check(Damm32::value(Damm32::ALPHABET[$i]) === $i, "value of alphabet[$i]");
}
foreach (str_split('01ilILoO!-_ ') as $char) {
    check(Damm32::value($char) === -1, "'$char' is not in the alphabet");
}
check(Damm32::value('ab') === -1, 'multi-character value is rejected');

/* Known values, case-insensitivity and invalid input. */
check(Damm32::checkDigit('fdfsdfds') === '3', 'fdfsdfds -> 3');
check(Damm32::isValid('fdfsdfds3'), 'fdfsdfds3 is valid');
check(!Damm32::isValid('fdfsdfds4'), 'fdfsdfds4 is invalid');
check(Damm32::isValid('FdFsDfDs3'), 'mixed case is valid');
check(Damm32::checkDigit('9j') === '*', '9j -> *');
check(Damm32::isValid('9j*'), '9j* is valid');
check(Damm32::checkDigit('bisantrene') === Damm32::INVALID, 'bisantrene is invalid');
check(!Damm32::isValid(''), 'empty string is invalid');

/* Shared vectors, identical to those used by the C and JavaScript tests. */
$count = 0;
foreach (file(__DIR__ . '/../../tests/vectors.txt', FILE_IGNORE_NEW_LINES) as $line) {
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    $space = strrpos($line, ' ');
    $code = substr($line, 0, $space);
    $expected = substr($line, $space + 1);
    check(Damm32::checkDigit($code) === $expected, "vector $code -> $expected");
    $count++;
}
check($count > 1000, 'shared vectors were read');

/* Every single-character substitution and adjacent transposition is detected. */
mt_srand(20141014);
for ($n = 0; $n < 500; $n++) {
    $code = '';
    for ($k = 0, $len = mt_rand(1, 12); $k < $len; $k++) {
        $code .= Damm32::ALPHABET[mt_rand(0, Damm32::BASE - 1)];
    }
    $code = withCheckDigit($code);
    check(Damm32::isValid($code), "$code round-trips");

    for ($pos = 0; $pos < strlen($code); $pos++) {
        foreach (str_split(Damm32::ALPHABET) as $char) {
            if ($char !== $code[$pos]) {
                $changed = $code;
                $changed[$pos] = $char;
                check(!Damm32::isValid($changed), "substitution in $changed detected");
            }
        }
        if ($pos + 1 < strlen($code) && $code[$pos] !== $code[$pos + 1]) {
            $swapped = substr($code, 0, $pos) . $code[$pos + 1] . $code[$pos] . substr($code, $pos + 2);
            check(!Damm32::isValid($swapped), "transposition in $swapped detected");
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "$failures check(s) failed\n");
    exit(1);
}

echo "All tests passed\n";
