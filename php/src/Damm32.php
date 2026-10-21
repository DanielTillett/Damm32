<?php

/*
 Base32 implementation of the Damm error detection algorithm in PHP

 Copyright (c) 2014, Daniel Tillett
 SPDX-License-Identifier: BSD-2-Clause
 */

declare(strict_types=1);

namespace Damm32;

final class Damm32
{
    public const BASE = 32;

    /*
     The base32 alphabet. It avoids characters that are easily confused with
     each other (0/o, 1/i/l), so the base32 zero is '2'. Input is
     case-insensitive. The first 31 characters match the original version;
     '*' replaces the duplicated 'z' that made check digit 31 unverifiable.
     */
    public const ALPHABET = '23456789abcdefghjkmnpqrstuvwxyz*';

    /* Returned by checkDigit() when the input contains a character outside the alphabet. */
    public const INVALID = '-';

    private const UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LOWER = 'abcdefghijklmnopqrstuvwxyz';

    /*
     WTA quasigroup matrix of order 32
     https://stackoverflow.com/questions/23431621/extending-the-damm-algorithm-to-base-32
     This was constructed according to Damm Lemma 5.2 by Michael
     (https://stackoverflow.com/users/3625116/michael)
     */
    private const MATRIX = [
        [ 0,  2,  4,  6,  8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30,  3,  1,  7,  5, 11,  9, 15, 13, 19, 17, 23, 21, 27, 25, 31, 29],
        [ 2,  0,  6,  4, 10,  8, 14, 12, 18, 16, 22, 20, 26, 24, 30, 28,  1,  3,  5,  7,  9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31],
        [ 4,  6,  0,  2, 12, 14,  8, 10, 20, 22, 16, 18, 28, 30, 24, 26,  7,  5,  3,  1, 15, 13, 11,  9, 23, 21, 19, 17, 31, 29, 27, 25],
        [ 6,  4,  2,  0, 14, 12, 10,  8, 22, 20, 18, 16, 30, 28, 26, 24,  5,  7,  1,  3, 13, 15,  9, 11, 21, 23, 17, 19, 29, 31, 25, 27],
        [ 8, 10, 12, 14,  0,  2,  4,  6, 24, 26, 28, 30, 16, 18, 20, 22, 11,  9, 15, 13,  3,  1,  7,  5, 27, 25, 31, 29, 19, 17, 23, 21],
        [10,  8, 14, 12,  2,  0,  6,  4, 26, 24, 30, 28, 18, 16, 22, 20,  9, 11, 13, 15,  1,  3,  5,  7, 25, 27, 29, 31, 17, 19, 21, 23],
        [12, 14,  8, 10,  4,  6,  0,  2, 28, 30, 24, 26, 20, 22, 16, 18, 15, 13, 11,  9,  7,  5,  3,  1, 31, 29, 27, 25, 23, 21, 19, 17],
        [14, 12, 10,  8,  6,  4,  2,  0, 30, 28, 26, 24, 22, 20, 18, 16, 13, 15,  9, 11,  5,  7,  1,  3, 29, 31, 25, 27, 21, 23, 17, 19],
        [16, 18, 20, 22, 24, 26, 28, 30,  0,  2,  4,  6,  8, 10, 12, 14, 19, 17, 23, 21, 27, 25, 31, 29,  3,  1,  7,  5, 11,  9, 15, 13],
        [18, 16, 22, 20, 26, 24, 30, 28,  2,  0,  6,  4, 10,  8, 14, 12, 17, 19, 21, 23, 25, 27, 29, 31,  1,  3,  5,  7,  9, 11, 13, 15],
        [20, 22, 16, 18, 28, 30, 24, 26,  4,  6,  0,  2, 12, 14,  8, 10, 23, 21, 19, 17, 31, 29, 27, 25,  7,  5,  3,  1, 15, 13, 11,  9],
        [22, 20, 18, 16, 30, 28, 26, 24,  6,  4,  2,  0, 14, 12, 10,  8, 21, 23, 17, 19, 29, 31, 25, 27,  5,  7,  1,  3, 13, 15,  9, 11],
        [24, 26, 28, 30, 16, 18, 20, 22,  8, 10, 12, 14,  0,  2,  4,  6, 27, 25, 31, 29, 19, 17, 23, 21, 11,  9, 15, 13,  3,  1,  7,  5],
        [26, 24, 30, 28, 18, 16, 22, 20, 10,  8, 14, 12,  2,  0,  6,  4, 25, 27, 29, 31, 17, 19, 21, 23,  9, 11, 13, 15,  1,  3,  5,  7],
        [28, 30, 24, 26, 20, 22, 16, 18, 12, 14,  8, 10,  4,  6,  0,  2, 31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11,  9,  7,  5,  3,  1],
        [30, 28, 26, 24, 22, 20, 18, 16, 14, 12, 10,  8,  6,  4,  2,  0, 29, 31, 25, 27, 21, 23, 17, 19, 13, 15,  9, 11,  5,  7,  1,  3],
        [ 3,  1,  7,  5, 11,  9, 15, 13, 19, 17, 23, 21, 27, 25, 31, 29,  0,  2,  4,  6,  8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30],
        [ 1,  3,  5,  7,  9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31,  2,  0,  6,  4, 10,  8, 14, 12, 18, 16, 22, 20, 26, 24, 30, 28],
        [ 7,  5,  3,  1, 15, 13, 11,  9, 23, 21, 19, 17, 31, 29, 27, 25,  4,  6,  0,  2, 12, 14,  8, 10, 20, 22, 16, 18, 28, 30, 24, 26],
        [ 5,  7,  1,  3, 13, 15,  9, 11, 21, 23, 17, 19, 29, 31, 25, 27,  6,  4,  2,  0, 14, 12, 10,  8, 22, 20, 18, 16, 30, 28, 26, 24],
        [11,  9, 15, 13,  3,  1,  7,  5, 27, 25, 31, 29, 19, 17, 23, 21,  8, 10, 12, 14,  0,  2,  4,  6, 24, 26, 28, 30, 16, 18, 20, 22],
        [ 9, 11, 13, 15,  1,  3,  5,  7, 25, 27, 29, 31, 17, 19, 21, 23, 10,  8, 14, 12,  2,  0,  6,  4, 26, 24, 30, 28, 18, 16, 22, 20],
        [15, 13, 11,  9,  7,  5,  3,  1, 31, 29, 27, 25, 23, 21, 19, 17, 12, 14,  8, 10,  4,  6,  0,  2, 28, 30, 24, 26, 20, 22, 16, 18],
        [13, 15,  9, 11,  5,  7,  1,  3, 29, 31, 25, 27, 21, 23, 17, 19, 14, 12, 10,  8,  6,  4,  2,  0, 30, 28, 26, 24, 22, 20, 18, 16],
        [19, 17, 23, 21, 27, 25, 31, 29,  3,  1,  7,  5, 11,  9, 15, 13, 16, 18, 20, 22, 24, 26, 28, 30,  0,  2,  4,  6,  8, 10, 12, 14],
        [17, 19, 21, 23, 25, 27, 29, 31,  1,  3,  5,  7,  9, 11, 13, 15, 18, 16, 22, 20, 26, 24, 30, 28,  2,  0,  6,  4, 10,  8, 14, 12],
        [23, 21, 19, 17, 31, 29, 27, 25,  7,  5,  3,  1, 15, 13, 11,  9, 20, 22, 16, 18, 28, 30, 24, 26,  4,  6,  0,  2, 12, 14,  8, 10],
        [21, 23, 17, 19, 29, 31, 25, 27,  5,  7,  1,  3, 13, 15,  9, 11, 22, 20, 18, 16, 30, 28, 26, 24,  6,  4,  2,  0, 14, 12, 10,  8],
        [27, 25, 31, 29, 19, 17, 23, 21, 11,  9, 15, 13,  3,  1,  7,  5, 24, 26, 28, 30, 16, 18, 20, 22,  8, 10, 12, 14,  0,  2,  4,  6],
        [25, 27, 29, 31, 17, 19, 21, 23,  9, 11, 13, 15,  1,  3,  5,  7, 26, 24, 30, 28, 18, 16, 22, 20, 10,  8, 14, 12,  2,  0,  6,  4],
        [31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11,  9,  7,  5,  3,  1, 28, 30, 24, 26, 20, 22, 16, 18, 12, 14,  8, 10,  4,  6,  0,  2],
        [29, 31, 25, 27, 21, 23, 17, 19, 13, 15,  9, 11,  5,  7,  1,  3, 30, 28, 26, 24, 22, 20, 18, 16, 14, 12, 10,  8,  6,  4,  2,  0]
    ];

    /**
     * Returns the value (0-31) of a base32 character, or -1 if it is not in the alphabet.
     */
    public static function value(string $char): int
    {
        if (strlen($char) !== 1) {
            return -1;
        }

        /* ASCII-only lowercase; strtolower() is locale-dependent before PHP 8.2. */
        $index = strpos(self::ALPHABET, strtr($char, self::UPPER, self::LOWER));

        return $index === false ? -1 : $index;
    }

    /**
     * Returns the base32 check digit for a string, or INVALID if the string
     * contains a character outside the alphabet.
     */
    public static function checkDigit(string $code): string
    {
        $interim = 0;

        for ($i = 0, $len = strlen($code); $i < $len; $i++) {
            $value = self::value($code[$i]);

            if ($value < 0) {
                return self::INVALID;
            }

            $interim = self::MATRIX[$interim][$value];
        }

        return self::ALPHABET[$interim];
    }

    /**
     * Returns true if the last character of a string is a valid Damm check
     * digit for the characters before it.
     */
    public static function isValid(string $code): bool
    {
        return $code !== '' && self::checkDigit($code) === self::ALPHABET[0];
    }
}
