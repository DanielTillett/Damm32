# damm32: base32 Damm check digits

The [Damm algorithm](https://en.wikipedia.org/wiki/Damm_algorithm) is a check digit algorithm that detects every single-character error and every adjacent transposition, the two most common transcription errors. Prepending leading zeros does not change the check digit, so it also works on fixed-width strings.

Most implementations are base 10 only. **damm32** applies the algorithm to base32 strings, with matching implementations in C, PHP and JavaScript. Each one is a library plus a command-line tool, and all three give identical output. Its alphabet leaves out easily confused characters (`0`/`o`, `1`/`i`/`l`), which makes it useful for human-entered codes such as passwords, licence keys and sample IDs.

```
23456789abcdefghjkmnpqrstuvwxyz*
```

| Implementation | Location | Requires |
| --- | --- | --- |
| C | [`include/`](include), [`src/`](src) | C99 compiler, CMake 3.16+ |
| PHP | [`php/`](php) | PHP 7.4+ |
| JavaScript | [`js/`](js) | Node.js 20+, or any modern browser |

Input is case-insensitive. Base32 zero is `2`. Because `*` is a shell wildcard, quote codes on the command line (for example `damm32 -c '9j*'`). The 32 × 32 weak totally anti-symmetric quasigroup comes from [this Stack Overflow answer](https://stackoverflow.com/questions/23431621/extending-the-damm-algorithm-to-base-32), built using Damm's Lemma 5.2.

## C

### Build

You need CMake 3.16 or later and a C99 compiler.

```bash
cmake -S . -B build -DCMAKE_BUILD_TYPE=Release
```

```bash
cmake --build build
```

```bash
ctest --test-dir build --output-on-failure
```

```bash
cmake --install build
```

If you don't have CMake, a single compiler call also works:

```bash
cc -std=c99 -O2 -Iinclude src/damm32.c src/main.c -o damm32
```

### Command-line usage

```
damm32 [-a | -c] [code ...]

  -a, --append   print each code with its check digit appended
  -c, --check    print nothing; exit 0 only if every code is valid
  -h, --help     show this help
  -V, --version  show the version
```

With no codes on the command line, `damm32` reads whitespace-separated codes from standard input. It exits with 0 on success, 1 if a code is invalid or contains a character outside the alphabet, and 2 on a usage error.

The default output is a report:

```
$ damm32 fdfsdfds
Base32 Number: fdfsdfds
Base32 Check Digit: 3
Error Check: 0

$ damm32 fdfsdfds3
Base32 Number: fdfsdfds3
Base32 Check Digit: 2
Error Check: 1
```

`Error Check` is 1 when the last character is the correct check digit for the rest of the string.

To generate a random 10-character code (9 random characters plus the check digit):

```bash
openssl rand -base64 50 | tr -dc '23456789abcdefghjkmnpqrstuvwxyz' | head -c9 | damm32 -a
```

To validate a code in a script:

```bash
damm32 -c "$code" && echo valid
```

### Library usage

```c
#include "damm32.h"

char digit = damm32_check_digit("fdfsdfds");  /* '3', or DAMM32_INVALID ('-') */
bool ok    = damm32_is_valid("fdfsdfds3");    /* true */
int value  = damm32_value('f');               /* 0-31, or -1 if not in the alphabet */
```

Link against the `damm32lib` CMake target, or compile `src/damm32.c` into your project.

## PHP

The PHP version has the same command-line interface as the C version.

```bash
php php/bin/damm32 -a fdfsdfds
```

```bash
php php/tests/Damm32Test.php
```

```php
use Damm32\Damm32;

Damm32::checkDigit('fdfsdfds');  // '3', or Damm32::INVALID ('-')
Damm32::isValid('fdfsdfds3');    // true
Damm32::value('f');              // 0-31, or -1 if not in the alphabet
```

Install it with Composer (the package is in [`php/`](php), with PSR-4 autoloading), or `require` [`php/src/Damm32.php`](php/src/Damm32.php) directly.

## JavaScript

The JavaScript version is a dependency-free ES module with TypeScript declarations. It works in Node.js and in the browser, and its command-line tool is the same as the C version.

```bash
node js/bin/damm32.js -a fdfsdfds
```

```bash
npm test --prefix js
```

```js
import { checkDigit, isValid, value, INVALID } from './damm32.js';

checkDigit('fdfsdfds');  // '3', or INVALID ('-')
isValid('fdfsdfds3');    // true
value('f');              // 0-31, or -1 if not in the alphabet
```

In a browser, load it with `<script type="module">` and import from `damm32.js`.

## Testing

[`tests/vectors.txt`](tests/vectors.txt) holds more than 1,500 codes and their check digits, generated from the C implementation. The C, PHP and JavaScript test suites all check against it, so the three implementations can't drift apart. Each suite also checks that every single-character substitution and adjacent transposition is detected. CI runs all three on every push.

## Changes in 2.0.0

- **Bug fix (C and PHP).** The original alphabet repeated `z` to reach 32 characters, so check value 31 was printed as `z` and then read back as 30. About 1 in 32 codes failed validation against their own check digit (for example, `damm32 9j` gave `z`, but `9jz` was reported as an error). The 32nd symbol is now `*`. Check digits for every other code are unchanged, so existing valid codes still validate.
- The PHP version is rewritten as a namespaced, strictly typed class with a CLI and tests, and has the same fix.
- New JavaScript (ES module) version.
- Input is now case-insensitive.
- An empty string is no longer reported as valid.
- The code is C99 and is split into a reusable library (`include/damm32.h`, `src/damm32.c`) and a CLI (`src/main.c`).
- New CLI options: `--append`, `--check`, `--help`, `--version`, plus standard input. Exit codes are now conventional (0 means success); previously the tool returned 1 on success.
- Added a CMake build, unit and CLI tests, and GitHub Actions CI (C on Linux, macOS and Windows with sanitisers; PHP 7.4–8.4; Node.js 20–24). The committed macOS binary has been removed.

## Licence

BSD 2-Clause. See [LICENSE](LICENSE).
