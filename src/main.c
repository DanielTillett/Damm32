/*
 Command-line interface for the base32 Damm check digit library.

 Copyright (c) 2015, Daniel Tillett
 SPDX-License-Identifier: BSD-2-Clause
 */

#if !defined(_WIN32) && !defined(_POSIX_C_SOURCE)
#define _POSIX_C_SOURCE 200809L /* fileno, isatty */
#endif

#include "damm32.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#ifdef _WIN32
#include <io.h>
#define isatty _isatty
#define fileno _fileno
#else
#include <unistd.h>
#endif

#ifndef DAMM32_VERSION
#define DAMM32_VERSION "2.0.0"
#endif

enum mode { MODE_REPORT, MODE_APPEND, MODE_CHECK };

enum exit_status { EXIT_OK = 0, EXIT_INVALID = 1, EXIT_USAGE = 2 };

static void usage(FILE *out) {
    fputs("Usage: damm32 [-a | -c] [code ...]\n"
          "\n"
          "Calculate or verify base32 Damm check digits. With no codes,\n"
          "whitespace-separated codes are read from standard input.\n"
          "\n"
          "  -a, --append   print each code with its check digit appended\n"
          "  -c, --check    print nothing; exit 0 only if every code is valid\n"
          "  -h, --help     show this help\n"
          "  -V, --version  show the version\n"
          "\n"
          "Alphabet: 23456789abcdefghjkmnpqrstuvwxyz* (case-insensitive)\n"
          "Exit status: 0 success, 1 invalid code or character, 2 usage error\n",
          out);
}

/* Processes one code and returns true if it passed for the selected mode. */
static bool process(const char *code, enum mode mode) {
    char digit = damm32_check_digit(code);

    switch (mode) {
    case MODE_APPEND:
        if (digit == DAMM32_INVALID) {
            fprintf(stderr, "damm32: invalid character in '%s'\n", code);
            return false;
        }
        printf("%s%c\n", code, digit);
        return true;

    case MODE_CHECK:
        return damm32_is_valid(code);

    case MODE_REPORT:
    default:
        printf("Base32 Number: %s\nBase32 Check Digit: %c\nError Check: %d\n",
               code, digit, damm32_is_valid(code) ? 1 : 0);
        return digit != DAMM32_INVALID;
    }
}

/* Reads the next whitespace-separated token from a stream. The caller frees it. */
static char *read_token(FILE *in) {
    size_t len = 0, cap = 64;
    char *buf = malloc(cap);
    int c;

    if (buf == NULL) {
        return NULL;
    }

    while ((c = getc(in)) != EOF && (c == ' ' || c == '\t' || c == '\n' || c == '\r')) {
    }

    while (c != EOF && c != ' ' && c != '\t' && c != '\n' && c != '\r') {
        if (len + 1 == cap) {
            char *grown = realloc(buf, cap *= 2);
            if (grown == NULL) {
                free(buf);
                return NULL;
            }
            buf = grown;
        }
        buf[len++] = (char)c;
        c = getc(in);
    }

    if (len == 0) {
        free(buf);
        return NULL;
    }

    buf[len] = '\0';
    return buf;
}

int main(int argc, char **argv) {
    enum mode mode = MODE_REPORT;
    int first = 1;
    bool ok = true;

    for (; first < argc && argv[first][0] == '-' && argv[first][1] != '\0'; first++) {
        const char *arg = argv[first];

        if (strcmp(arg, "--") == 0) {
            first++;
            break;
        } else if (strcmp(arg, "-a") == 0 || strcmp(arg, "--append") == 0) {
            mode = MODE_APPEND;
        } else if (strcmp(arg, "-c") == 0 || strcmp(arg, "--check") == 0) {
            mode = MODE_CHECK;
        } else if (strcmp(arg, "-h") == 0 || strcmp(arg, "--help") == 0) {
            usage(stdout);
            return EXIT_OK;
        } else if (strcmp(arg, "-V") == 0 || strcmp(arg, "--version") == 0) {
            puts("damm32 " DAMM32_VERSION);
            return EXIT_OK;
        } else {
            fprintf(stderr, "damm32: unknown option '%s'\n", arg);
            usage(stderr);
            return EXIT_USAGE;
        }
    }

    if (first < argc) {
        for (int i = first; i < argc; i++) {
            ok = process(argv[i], mode) && ok;
        }
    } else if (isatty(fileno(stdin))) {
        usage(stderr);
        return EXIT_USAGE;
    } else {
        char *code;
        bool any = false;

        while ((code = read_token(stdin)) != NULL) {
            any = true;
            ok = process(code, mode) && ok;
            free(code);
        }

        if (!any) {
            usage(stderr);
            return EXIT_USAGE;
        }
    }

    return ok ? EXIT_OK : EXIT_INVALID;
}
