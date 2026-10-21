/*
 Base32 implementation of the Damm error detection algorithm in C

 Copyright (c) 2015, Daniel Tillett
 SPDX-License-Identifier: BSD-2-Clause
 */

#ifndef DAMM32_H
#define DAMM32_H

#include <stdbool.h>

#ifdef __cplusplus
extern "C" {
#endif

#define DAMM32_BASE 32

/* Returned by damm32_check_digit() when the input contains a character
   outside the base32 alphabet. */
#define DAMM32_INVALID '-'

/*
 The base32 alphabet. It avoids characters that are easily confused with each
 other (0/o, 1/i/l), so the base32 zero is '2'. Input is case-insensitive.
 */
extern const char damm32_alphabet[DAMM32_BASE + 1];

/*
 Returns the value (0-31) of a base32 character, or -1 if the character is not
 in the alphabet.
 */
int damm32_value(char c);

/*
 Returns the base32 check digit for a null-terminated base32 string, or
 DAMM32_INVALID if the string contains a character outside the alphabet.
 */
char damm32_check_digit(const char *code);

/*
 Returns true if the last character of a null-terminated base32 string is a
 valid Damm check digit for the characters before it.
 */
bool damm32_is_valid(const char *code);

#ifdef __cplusplus
}
#endif

#endif /* DAMM32_H */
