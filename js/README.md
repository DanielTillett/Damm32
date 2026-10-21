# damm32

Base32 check digits using the [Damm algorithm](https://en.wikipedia.org/wiki/Damm_algorithm), which detects every single-character error and every adjacent transposition. Useful for human-entered codes such as licence keys, voucher codes and sample IDs.

The alphabet leaves out easily confused characters (`0`/`o`, `1`/`i`/`l`):

```
23456789abcdefghjkmnpqrstuvwxyz*
```

Input is case-insensitive. No dependencies; works in Node.js 20+ and modern browsers. TypeScript declarations are included.

## Install

```bash
npm install damm32
```

## Usage

```js
import { checkDigit, isValid, value, INVALID } from 'damm32';

checkDigit('fdfsdfds');  // '3', or INVALID ('-') if a character is outside the alphabet
isValid('fdfsdfds3');    // true
isValid('fdfsdfd3s');    // false (transposition detected)
value('f');              // 0-31, or -1 if not in the alphabet
```

## Command line

```bash
npx damm32 -a fdfsdfds        # fdfsdfds3
npx damm32 -c fdfsdfds3       # exit status 0 if valid, 1 if not
```

Run `npx damm32 --help` for all options. Quote codes containing `*` in the shell.

C and PHP implementations with identical output are in the [GitHub repository](https://github.com/DanielTillett/Damm32).

## Licence

BSD 2-Clause
