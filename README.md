##Base32 Implementation of Damm Error Checking 
The Damm algorithm is a fantastic check digit algorithm that detects all single-digit errors and all adjacent transposition errors. It detects all occurrences of altering one single digit and all occurrences of transposing two adjacent digits, the two most frequent transcription errors. The Damm algorithm also has the benefit that prepending leading base encoding zeros does not affect the check digit so you can use it on fixed size strings. All in all it is a great algorithm for checking if there have been any user errors when entering a number.

The only problem with the Damm algorithm is that all the current implementations are base 10 (0-9) only. I wanted to be able to use it to check alpha-numerical strings (passwords) so I wrote a base32 implementation of the Damm algorithm in C/C++. With the commonly confused characters (0/o and 1/l/i) avoided in the base32 encoding you can make a rather nice password checker.

##Usage

I use the damn32 program in a bash one-liner to generate 10 (9 + the damn check) character password strings. You can make longer or shorter strings by changing the head -c switch.

`openssl rand -base64 50 | tr -dc '23456789abcdefghjkmnpqrstuvwxyz' | head -c9 | xargs damn32`

##Compiling
**damm32.c** can be compiled with gcc (or any other C compiler) using:

`gcc damn32.c -o damn32`