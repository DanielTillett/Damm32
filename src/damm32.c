/*
 Base32 implementation of the Damm error detection algorithm in C

 Copyright (c) 2015, Daniel Tillett
 All rights reserved.

 Redistribution and use in source and binary forms, with or without
 modification, are permitted provided that the following conditions are met:

 1. Redistributions of source code must retain the above copyright notice, this
 list of conditions and the following disclaimer.
 2. Redistributions in binary form must reproduce the above copyright notice,
 this list of conditions and the following disclaimer in the documentation
 and/or other materials provided with the distribution.

 THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

 Background
 The Damm algorithm is a check digit algorithm that detects all single-digit errors
 and all adjacent transposition errors.

 It detects all occurrences of altering one single digit and all occurrences of
 transposing two adjacent digits, the two most frequent transcription errors.
 The Damm algorithm has the benefit that it makes do without the dedicatedly
 constructed permutations and its position specific powers being inherent in the
 Verhoeff scheme. Prepending leading base encoding zeros does not affect the check digit.
 https://en.wikipedia.org/wiki/Damm_algorithm
 */

#include "damm32.h"

#include <ctype.h>

/*
 The first 31 characters are the original alphabet, so check digits 0-30 are
 unchanged. Earlier versions padded the alphabet with a second 'z', which made
 check digit 31 impossible to validate; '*' is used as the 32nd symbol instead
 because it cannot be confused with any letter or digit.
 */
const char damm32_alphabet[DAMM32_BASE + 1] = "23456789abcdefghjkmnpqrstuvwxyz*";

/*
 WTA quasigroup matrix of order 32
 https://stackoverflow.com/questions/23431621/extending-the-damm-algorithm-to-base-32
 This was constructed according to Damm Lemma 5.2 by Michael
 (https://stackoverflow.com/users/3625116/michael)
 */
static const unsigned char damm32_matrix[DAMM32_BASE][DAMM32_BASE] = {
    { 0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 3, 1, 7, 5, 11, 9, 15, 13, 19, 17, 23, 21, 27, 25, 31, 29},
    { 2, 0, 6, 4, 10, 8, 14, 12, 18, 16, 22, 20, 26, 24, 30, 28, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31},
    { 4, 6, 0, 2, 12, 14, 8, 10, 20, 22, 16, 18, 28, 30, 24, 26, 7, 5, 3, 1, 15, 13, 11, 9, 23, 21, 19, 17, 31, 29, 27, 25},
    { 6, 4, 2, 0, 14, 12, 10, 8, 22, 20, 18, 16, 30, 28, 26, 24, 5, 7, 1, 3, 13, 15, 9, 11, 21, 23, 17, 19, 29, 31, 25, 27},
    { 8, 10, 12, 14, 0, 2, 4, 6, 24, 26, 28, 30, 16, 18, 20, 22, 11, 9, 15, 13, 3, 1, 7, 5, 27, 25, 31, 29, 19, 17, 23, 21},
    {10, 8, 14, 12, 2, 0, 6, 4, 26, 24, 30, 28, 18, 16, 22, 20, 9, 11, 13, 15, 1, 3, 5, 7, 25, 27, 29, 31, 17, 19, 21, 23},
    {12, 14, 8, 10, 4, 6, 0, 2, 28, 30, 24, 26, 20, 22, 16, 18, 15, 13, 11, 9, 7, 5, 3, 1, 31, 29, 27, 25, 23, 21, 19, 17},
    {14, 12, 10, 8, 6, 4, 2, 0, 30, 28, 26, 24, 22, 20, 18, 16, 13, 15, 9, 11, 5, 7, 1, 3, 29, 31, 25, 27, 21, 23, 17, 19},
    {16, 18, 20, 22, 24, 26, 28, 30, 0, 2, 4, 6, 8, 10, 12, 14, 19, 17, 23, 21, 27, 25, 31, 29, 3, 1, 7, 5, 11, 9, 15, 13},
    {18, 16, 22, 20, 26, 24, 30, 28, 2, 0, 6, 4, 10, 8, 14, 12, 17, 19, 21, 23, 25, 27, 29, 31, 1, 3, 5, 7, 9, 11, 13, 15},
    {20, 22, 16, 18, 28, 30, 24, 26, 4, 6, 0, 2, 12, 14, 8, 10, 23, 21, 19, 17, 31, 29, 27, 25, 7, 5, 3, 1, 15, 13, 11, 9},
    {22, 20, 18, 16, 30, 28, 26, 24, 6, 4, 2, 0, 14, 12, 10, 8, 21, 23, 17, 19, 29, 31, 25, 27, 5, 7, 1, 3, 13, 15, 9, 11},
    {24, 26, 28, 30, 16, 18, 20, 22, 8, 10, 12, 14, 0, 2, 4, 6, 27, 25, 31, 29, 19, 17, 23, 21, 11, 9, 15, 13, 3, 1, 7, 5},
    {26, 24, 30, 28, 18, 16, 22, 20, 10, 8, 14, 12, 2, 0, 6, 4, 25, 27, 29, 31, 17, 19, 21, 23, 9, 11, 13, 15, 1, 3, 5, 7},
    {28, 30, 24, 26, 20, 22, 16, 18, 12, 14, 8, 10, 4, 6, 0, 2, 31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1},
    {30, 28, 26, 24, 22, 20, 18, 16, 14, 12, 10, 8, 6, 4, 2, 0, 29, 31, 25, 27, 21, 23, 17, 19, 13, 15, 9, 11, 5, 7, 1, 3},
    { 3, 1, 7, 5, 11, 9, 15, 13, 19, 17, 23, 21, 27, 25, 31, 29, 0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30},
    { 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31, 2, 0, 6, 4, 10, 8, 14, 12, 18, 16, 22, 20, 26, 24, 30, 28},
    { 7, 5, 3, 1, 15, 13, 11, 9, 23, 21, 19, 17, 31, 29, 27, 25, 4, 6, 0, 2, 12, 14, 8, 10, 20, 22, 16, 18, 28, 30, 24, 26},
    { 5, 7, 1, 3, 13, 15, 9, 11, 21, 23, 17, 19, 29, 31, 25, 27, 6, 4, 2, 0, 14, 12, 10, 8, 22, 20, 18, 16, 30, 28, 26, 24},
    {11, 9, 15, 13, 3, 1, 7, 5, 27, 25, 31, 29, 19, 17, 23, 21, 8, 10, 12, 14, 0, 2, 4, 6, 24, 26, 28, 30, 16, 18, 20, 22},
    { 9, 11, 13, 15, 1, 3, 5, 7, 25, 27, 29, 31, 17, 19, 21, 23, 10, 8, 14, 12, 2, 0, 6, 4, 26, 24, 30, 28, 18, 16, 22, 20},
    {15, 13, 11, 9, 7, 5, 3, 1, 31, 29, 27, 25, 23, 21, 19, 17, 12, 14, 8, 10, 4, 6, 0, 2, 28, 30, 24, 26, 20, 22, 16, 18},
    {13, 15, 9, 11, 5, 7, 1, 3, 29, 31, 25, 27, 21, 23, 17, 19, 14, 12, 10, 8, 6, 4, 2, 0, 30, 28, 26, 24, 22, 20, 18, 16},
    {19, 17, 23, 21, 27, 25, 31, 29, 3, 1, 7, 5, 11, 9, 15, 13, 16, 18, 20, 22, 24, 26, 28, 30, 0, 2, 4, 6, 8, 10, 12, 14},
    {17, 19, 21, 23, 25, 27, 29, 31, 1, 3, 5, 7, 9, 11, 13, 15, 18, 16, 22, 20, 26, 24, 30, 28, 2, 0, 6, 4, 10, 8, 14, 12},
    {23, 21, 19, 17, 31, 29, 27, 25, 7, 5, 3, 1, 15, 13, 11, 9, 20, 22, 16, 18, 28, 30, 24, 26, 4, 6, 0, 2, 12, 14, 8, 10},
    {21, 23, 17, 19, 29, 31, 25, 27, 5, 7, 1, 3, 13, 15, 9, 11, 22, 20, 18, 16, 30, 28, 26, 24, 6, 4, 2, 0, 14, 12, 10, 8},
    {27, 25, 31, 29, 19, 17, 23, 21, 11, 9, 15, 13, 3, 1, 7, 5, 24, 26, 28, 30, 16, 18, 20, 22, 8, 10, 12, 14, 0, 2, 4, 6},
    {25, 27, 29, 31, 17, 19, 21, 23, 9, 11, 13, 15, 1, 3, 5, 7, 26, 24, 30, 28, 18, 16, 22, 20, 10, 8, 14, 12, 2, 0, 6, 4},
    {31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1, 28, 30, 24, 26, 20, 22, 16, 18, 12, 14, 8, 10, 4, 6, 0, 2},
    {29, 31, 25, 27, 21, 23, 17, 19, 13, 15, 9, 11, 5, 7, 1, 3, 30, 28, 26, 24, 22, 20, 18, 16, 14, 12, 10, 8, 6, 4, 2, 0}
};

int damm32_value(char c) {
    int lower = tolower((unsigned char)c);

    for (int i = 0; i < DAMM32_BASE; i++) {
        if (lower == damm32_alphabet[i]) {
            return i;
        }
    }

    return -1;
}

char damm32_check_digit(const char *code) {
    int interim = 0;

    for (const char *p = code; *p != '\0'; ++p) {
        int i = damm32_value(*p);

        if (i < 0) {
            return DAMM32_INVALID;
        }

        interim = damm32_matrix[interim][i];
    }

    return damm32_alphabet[interim];
}

bool damm32_is_valid(const char *code) {
    return code[0] != '\0' && damm32_check_digit(code) == damm32_alphabet[0];
}
