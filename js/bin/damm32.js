#!/usr/bin/env node

/*
 Command-line interface for the base32 Damm check digit library.
 Behaves the same as the C version.

 Copyright (c) 2015, Daniel Tillett
 SPDX-License-Identifier: BSD-2-Clause
 */

import { readFileSync } from 'node:fs';
import { INVALID, checkDigit, isValid } from '../damm32.js';

const VERSION = '2.0.0';
const EXIT_OK = 0;
const EXIT_INVALID = 1;
const EXIT_USAGE = 2;

const USAGE = `Usage: damm32 [-a | -c] [code ...]

Calculate or verify base32 Damm check digits. With no codes,
whitespace-separated codes are read from standard input.

  -a, --append   print each code with its check digit appended
  -c, --check    print nothing; exit 0 only if every code is valid
  -h, --help     show this help
  -V, --version  show the version

Alphabet: 23456789abcdefghjkmnpqrstuvwxyz* (case-insensitive)
Exit status: 0 success, 1 invalid code or character, 2 usage error
`;

/* Processes one code and returns true if it passed for the selected mode. */
function processCode(code, mode) {
  const digit = checkDigit(code);

  switch (mode) {
    case 'append':
      if (digit === INVALID) {
        process.stderr.write(`damm32: invalid character in '${code}'\n`);
        return false;
      }
      process.stdout.write(`${code}${digit}\n`);
      return true;

    case 'check':
      return isValid(code);

    default:
      process.stdout.write(
        `Base32 Number: ${code}\nBase32 Check Digit: ${digit}\nError Check: ${isValid(code) ? 1 : 0}\n`,
      );
      return digit !== INVALID;
  }
}

function main(argv) {
  let mode = 'report';
  const args = [...argv];

  while (args.length > 0 && args[0].length > 1 && args[0][0] === '-') {
    const arg = args.shift();

    if (arg === '--') {
      break;
    } else if (arg === '-a' || arg === '--append') {
      mode = 'append';
    } else if (arg === '-c' || arg === '--check') {
      mode = 'check';
    } else if (arg === '-h' || arg === '--help') {
      process.stdout.write(USAGE);
      return EXIT_OK;
    } else if (arg === '-V' || arg === '--version') {
      process.stdout.write(`damm32 ${VERSION}\n`);
      return EXIT_OK;
    } else {
      process.stderr.write(`damm32: unknown option '${arg}'\n${USAGE}`);
      return EXIT_USAGE;
    }
  }

  let codes = args;

  if (codes.length === 0) {
    if (process.stdin.isTTY) {
      process.stderr.write(USAGE);
      return EXIT_USAGE;
    }
    codes = readFileSync(0, 'utf8').split(/\s+/).filter(Boolean);
    if (codes.length === 0) {
      process.stderr.write(USAGE);
      return EXIT_USAGE;
    }
  }

  let ok = true;
  for (const code of codes) {
    ok = processCode(code, mode) && ok;
  }

  return ok ? EXIT_OK : EXIT_INVALID;
}

process.exitCode = main(process.argv.slice(2));
