/*
 Tests for the JavaScript base32 Damm check digit library. Run with `npm test`.

 Copyright (c) 2015, Daniel Tillett
 SPDX-License-Identifier: BSD-2-Clause
 */

import assert from 'node:assert/strict';
import { execFileSync, spawnSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';
import { ALPHABET, INVALID, checkDigit, isValid, value } from './damm32.js';

const VECTORS = fileURLToPath(new URL('../tests/vectors.txt', import.meta.url));
const CLI = fileURLToPath(new URL('./bin/damm32.js', import.meta.url));

/* Small deterministic PRNG so the tests behave identically everywhere. */
function rng(seed) {
  let state = seed >>> 0;
  return (bound) => {
    state = (Math.imul(state, 1103515245) + 12345) >>> 0;
    return (state >>> 16) % bound;
  };
}

test('alphabet is a bijection that excludes confusable characters', () => {
  assert.equal(ALPHABET.length, 32);
  assert.equal(new Set(ALPHABET).size, 32);
  [...ALPHABET].forEach((char, i) => assert.equal(value(char), i));
  for (const char of '01ilILoO!-_ ') {
    assert.equal(value(char), -1, `'${char}' should not be in the alphabet`);
  }
  assert.equal(value('ab'), -1);
});

test('known values, case-insensitivity and invalid input', () => {
  assert.equal(checkDigit('fdfsdfds'), '3');
  assert.ok(isValid('fdfsdfds3'));
  assert.ok(!isValid('fdfsdfds4'));
  assert.ok(isValid('FdFsDfDs3'));
  assert.equal(checkDigit('9j'), '*');
  assert.ok(isValid('9j*'));
  assert.equal(checkDigit('invalid'), INVALID);
  assert.equal(checkDigit('İ'), INVALID);
  assert.ok(!isValid(''));
  assert.throws(() => checkDigit(42), TypeError);
});

test('shared vectors match the C reference implementation', () => {
  let count = 0;
  for (const line of readFileSync(VECTORS, 'utf8').split('\n')) {
    if (line === '' || line.startsWith('#')) {
      continue;
    }
    const space = line.lastIndexOf(' ');
    const code = line.slice(0, space);
    assert.equal(checkDigit(code), line.slice(space + 1), `vector ${code}`);
    count++;
  }
  assert.ok(count > 1000);
});

test('detects every single substitution and adjacent transposition', () => {
  const next = rng(20151222);

  for (let n = 0; n < 500; n++) {
    let code = '';
    for (let k = 0, len = 1 + next(12); k < len; k++) {
      code += ALPHABET[next(32)];
    }
    code += checkDigit(code);
    assert.ok(isValid(code));

    for (let pos = 0; pos < code.length; pos++) {
      for (const char of ALPHABET) {
        if (char !== code[pos]) {
          const changed = code.slice(0, pos) + char + code.slice(pos + 1);
          assert.ok(!isValid(changed), `substitution in ${changed}`);
        }
      }
      if (pos + 1 < code.length && code[pos] !== code[pos + 1]) {
        const swapped = code.slice(0, pos) + code[pos + 1] + code[pos] + code.slice(pos + 2);
        assert.ok(!isValid(swapped), `transposition in ${swapped}`);
      }
    }
  }
});

test('CLI matches the C version', () => {
  const run = (args, input) => spawnSync(process.execPath, [CLI, ...args], { input, encoding: 'utf8' });

  assert.equal(
    execFileSync(process.execPath, [CLI, 'fdfsdfds'], { encoding: 'utf8' }),
    'Base32 Number: fdfsdfds\nBase32 Check Digit: 3\nError Check: 0\n',
  );
  assert.equal(run(['-a', 'fdfsdfds', '9j']).stdout, 'fdfsdfds3\n9j*\n');
  assert.equal(run(['-a'], 'fdfsdfds\n9j\n').stdout, 'fdfsdfds3\n9j*\n');
  assert.equal(run(['-c', 'fdfsdfds3', '9j*']).status, 0);
  assert.equal(run(['-c', 'fdfsdfds4']).status, 1);
  assert.equal(run(['-a', 'invalid']).status, 1);
  assert.equal(run(['--bogus']).status, 2);
});
