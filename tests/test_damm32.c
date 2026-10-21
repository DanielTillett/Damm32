/*
 Unit tests for the base32 Damm check digit library.

 Copyright (c) 2015, Daniel Tillett
 SPDX-License-Identifier: BSD-2-Clause
 */

#include "damm32.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

static int failures;

#define CHECK(cond)                                                            \
    do {                                                                       \
        if (!(cond)) {                                                         \
            fprintf(stderr, "%s:%d: CHECK failed: %s\n", __FILE__, __LINE__,   \
                    #cond);                                                    \
            failures++;                                                        \
        }                                                                      \
    } while (0)

/* Small deterministic PRNG so the tests behave identically everywhere. */
static unsigned long rng_state = 20151222UL;

static int rng_next(int bound) {
    rng_state = rng_state * 1103515245UL + 12345UL;
    return (int)((rng_state >> 16) % (unsigned long)bound);
}

static void random_code(char *buf, size_t len) {
    for (size_t i = 0; i < len; i++) {
        buf[i] = damm32_alphabet[rng_next(DAMM32_BASE)];
    }
    buf[len] = '\0';
}

static void append_check_digit(char *buf) {
    size_t len = strlen(buf);
    buf[len] = damm32_check_digit(buf);
    buf[len + 1] = '\0';
}

static void test_alphabet_is_a_bijection(void) {
    CHECK(strlen(damm32_alphabet) == DAMM32_BASE);

    for (int i = 0; i < DAMM32_BASE; i++) {
        CHECK(damm32_value(damm32_alphabet[i]) == i);
    }

    const char *excluded = "01ilILoO!-_ ";
    for (const char *p = excluded; *p != '\0'; p++) {
        CHECK(damm32_value(*p) == -1);
    }
}

/* Check digits produced by the original 2015 implementation. */
static void test_backward_compatible_vectors(void) {
    static const struct {
        const char *code;
        char digit;
    } vectors[] = {
        {"fdfsdfds", '3'},       {"2", '2'},    {"22222", '2'},
        {"abc", 'q'},            {"zzzz", 'c'}, {"hjkmnpqrstuvwxyz", 'j'},
        {"damm", 'j'},           {"q", 'b'},
    };

    for (size_t i = 0; i < sizeof vectors / sizeof vectors[0]; i++) {
        CHECK(damm32_check_digit(vectors[i].code) == vectors[i].digit);
    }

    CHECK(damm32_is_valid("fdfsdfds3"));
    CHECK(!damm32_is_valid("fdfsdfds4"));
}

/* The original alphabet ended "zz", so check digit 31 was printed as 'z' and
   never validated (e.g. "9j" -> "9jz" was reported as an error). */
static void test_check_digit_31_round_trips(void) {
    static const char *codes[] = {"9j", "ag", "f6", "mu"};

    for (size_t i = 0; i < sizeof codes / sizeof codes[0]; i++) {
        char buf[16];
        strcpy(buf, codes[i]);
        CHECK(damm32_check_digit(buf) == '*');
        append_check_digit(buf);
        CHECK(damm32_is_valid(buf));
    }
}

static void test_case_insensitive(void) {
    CHECK(damm32_check_digit("FDFSDFDS") == '3');
    CHECK(damm32_is_valid("FdFsDfDs3"));
}

static void test_invalid_input(void) {
    CHECK(damm32_check_digit("invalid") == DAMM32_INVALID);
    CHECK(damm32_check_digit("abc0") == DAMM32_INVALID);
    CHECK(damm32_check_digit("ab c") == DAMM32_INVALID);
    CHECK(!damm32_is_valid("abc0"));
    CHECK(!damm32_is_valid(""));
    CHECK(damm32_check_digit("") == damm32_alphabet[0]);
}

static void test_leading_zeros_do_not_change_check_digit(void) {
    for (int n = 0; n < 1000; n++) {
        char code[32], padded[40];
        random_code(code, (size_t)(1 + rng_next(12)));
        snprintf(padded, sizeof padded, "222%s", code);
        CHECK(damm32_check_digit(code) == damm32_check_digit(padded));
    }
}

static void test_every_two_character_code_round_trips(void) {
    for (int a = 0; a < DAMM32_BASE; a++) {
        for (int b = 0; b < DAMM32_BASE; b++) {
            char buf[4] = {damm32_alphabet[a], damm32_alphabet[b], '\0', '\0'};
            append_check_digit(buf);
            CHECK(damm32_is_valid(buf));
        }
    }
}

/* Damm detects every single-character substitution and every adjacent
   transposition, including errors in the check digit itself. */
static void test_detects_single_errors_and_transpositions(void) {
    for (int n = 0; n < 2000; n++) {
        char code[32];
        size_t len = (size_t)(1 + rng_next(12));
        random_code(code, len);
        append_check_digit(code);
        CHECK(damm32_is_valid(code));

        for (size_t pos = 0; pos <= len; pos++) {
            char original = code[pos];
            for (int v = 0; v < DAMM32_BASE; v++) {
                if (damm32_alphabet[v] == original) {
                    continue;
                }
                code[pos] = damm32_alphabet[v];
                CHECK(!damm32_is_valid(code));
            }
            code[pos] = original;
        }

        for (size_t pos = 0; pos < len; pos++) {
            if (code[pos] == code[pos + 1]) {
                continue;
            }
            char tmp = code[pos];
            code[pos] = code[pos + 1];
            code[pos + 1] = tmp;
            CHECK(!damm32_is_valid(code));
            code[pos + 1] = code[pos];
            code[pos] = tmp;
        }
    }
}

/* Checks the shared vectors that the PHP and JavaScript tests also use. */
static void test_shared_vectors(const char *path) {
    FILE *f = fopen(path, "r");
    char line[256];
    int count = 0;

    CHECK(f != NULL);
    if (f == NULL) {
        return;
    }

    while (fgets(line, sizeof line, f) != NULL) {
        char *space = strrchr(line, ' ');

        if (line[0] == '#' || space == NULL) {
            continue;
        }

        *space = '\0';
        if (damm32_check_digit(line) != space[1]) {
            fprintf(stderr, "vector '%s': got %c, expected %c\n", line,
                    damm32_check_digit(line), space[1]);
            failures++;
        }
        count++;
    }

    fclose(f);
    CHECK(count > 1000);
}

int main(int argc, char **argv) {
    test_alphabet_is_a_bijection();
    test_backward_compatible_vectors();
    test_check_digit_31_round_trips();
    test_case_insensitive();
    test_invalid_input();
    test_leading_zeros_do_not_change_check_digit();
    test_every_two_character_code_round_trips();
    test_detects_single_errors_and_transpositions();

    if (argc > 1) {
        test_shared_vectors(argv[1]);
    }

    if (failures != 0) {
        fprintf(stderr, "%d check(s) failed\n", failures);
        return EXIT_FAILURE;
    }

    puts("All tests passed");
    return EXIT_SUCCESS;
}
