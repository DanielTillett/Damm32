##Base32 Implementation of Damm Error Checking 
The Damm algorithm is a fantastic check digit algorithm that detects all single-digit errors and all adjacent transposition errors[1]. It detects all occurrences of altering one single digit and all occurrences of transposing two adjacent digits, the two most frequent transcription errors. The Damm algorithm also has the benefit that prepending leading base encoding zeros does not affect the check digit so you can use it on fixed size strings. All in all it is a great algorithm for checking if there have been any user errors when entering a number.

The only issue with the Damm algorithm is that all the current implementations are base10 (0-9) only. I wanted to be able to use it to check alpha-numerical strings (passwords) so I wrote a base32 implementation of the Damm algorithm in C/C++ and PHP. With the commonly confused characters (0/o and 1/l/i) avoided in the encoding it makes a rather nice password checker. Any desired encoding scheme with 32 characters (or less) can be used.

[1] https://en.wikipedia.org/wiki/Damm_algorithm

##C Version

###Usage
The basic call of **damm32** is:

`./damm32 [base32 number]`

This outputs the following when using the base32 number **fdfsdfds**

`Input: fdfsdfds`

`Check Digit: 3`

`Checked: 0`

If the last digit in the input number equals the expected Damm check digit then *Checked* will be 1.

`Input: fdfsdfds3`

`Check Digit: 2`

`Checked: 1`

If the input contains characters not contained within the encoding scheme then the *Check Digit* will be '-'.

`Input: fdfsdfds3`

`Check Digit: -`

`Checked: 0`


**damm32** can be used in a bash one-liner to generate any length (x + the damn check digit) character password strings. You can make longer or shorter strings by changing the `head -c` switch value. If wanting to generating really long strings increase the `openssl rand -base64` value to ~4x. Here is an example for generate a 10 digit string:

`openssl rand -base64 50 | tr -dc '23456789abcdefghjkmnpqrstuvwxyz' | head -c9 | xargs damm32`

###Compile
**damm32.c** can be compiled with gcc (and most likely any other C compiler) using:

`gcc damm32.c -o damm32`

I have include the compiled MacOS X version (`damm32`).

##PHP Version
I have ported the C version to PHP (`damm32.php`). It can be run using the **php cli**. The output is the same as the C version.

`php damm32.php [base32 number]`