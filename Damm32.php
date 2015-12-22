<?php
/*
    Base32 implementation of the Damm error detection algorithm in PHP

    Copyright (c) 2014, Daniel Tillett
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

/*
    A base32 encoding that avoids using characters that could be confused with each other 
    (i.e. 0/o or 1/i/l).
    With this encoding the base32 0 is '2'. 
    
    The encoding can be changed to any encoding scheme. If using less than 32 characters 
    in your encoding then just repeat one or more of the characters in your set.
*/
$base32 = "23456789abcdefghjkmnpqrstuvwxyzz";

/*
    Returns index position of base32 digit if the digit exists in the base32 character set
    or 32 if not.
*/
function base32Index($p) {
	global $base32;
	for ($i = 0; $i < 32; $i++) {
		
		//echo $base32[$i];
		if ($p == $base32[$i]) {
			break;
			}
		}
	return $i;
	}

/*
    Damn algorithm base32 check digit calculator
    Returns the base32 check digit defined by base32 string or '-' if base32 
    number contains a character that is not defined in the base32 string. 
    Input must be a php string.
*/
function damm32Encode($code) {
	$interim = 0;
	$len = strlen($code);
	global $base32;
	
	/*
	    WTA quasigroup matrix of order 32
	    http://stackoverflow.com/questions/23431621/extending-the-damm-algorithm-to-base-32
	    This was constructed according to Damm Lemma 5.2 by Michael 
	    (http://stackoverflow.com/users/3625116/michael)
	    I suspect this Michael is the Michael Damm, but I really don't know :)
	*/
	$damm32Matrix = array(
			array (0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 3, 1, 7, 5, 11, 9, 15, 13, 19, 17, 23, 21, 27, 25, 31, 29 ),
			array ( 2, 0, 6, 4, 10, 8, 14, 12, 18, 16, 22, 20, 26, 24, 30, 28, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31),
			array ( 4, 6, 0, 2, 12, 14, 8, 10, 20, 22, 16, 18, 28, 30, 24, 26, 7, 5, 3, 1, 15, 13, 11, 9, 23, 21, 19, 17, 31, 29, 27, 25),
			array ( 6, 4, 2, 0, 14, 12, 10, 8, 22, 20, 18, 16, 30, 28, 26, 24, 5, 7, 1, 3, 13, 15, 9, 11, 21, 23, 17, 19, 29, 31, 25, 27),
			array ( 8, 10, 12, 14, 0, 2, 4, 6, 24, 26, 28, 30, 16, 18, 20, 22, 11, 9, 15, 13, 3, 1, 7, 5, 27, 25, 31, 29, 19, 17, 23, 21),
			array (10, 8, 14, 12, 2, 0, 6, 4, 26, 24, 30, 28, 18, 16, 22, 20, 9, 11, 13, 15, 1, 3, 5, 7, 25, 27, 29, 31, 17, 19, 21, 23 ),
			array (12, 14, 8, 10, 4, 6, 0, 2, 28, 30, 24, 26, 20, 22, 16, 18, 15, 13, 11, 9, 7, 5, 3, 1, 31, 29, 27, 25, 23, 21, 19, 17 ),
			array (14, 12, 10, 8, 6, 4, 2, 0, 30, 28, 26, 24, 22, 20, 18, 16, 13, 15, 9, 11, 5, 7, 1, 3, 29, 31, 25, 27, 21, 23, 17, 19 ),
			array (16, 18, 20, 22, 24, 26, 28, 30, 0, 2, 4, 6, 8, 10, 12, 14, 19, 17, 23, 21, 27, 25, 31, 29, 3, 1, 7, 5, 11, 9, 15, 13 ),
			array (18, 16, 22, 20, 26, 24, 30, 28, 2, 0, 6, 4, 10, 8, 14, 12, 17, 19, 21, 23, 25, 27, 29, 31, 1, 3, 5, 7, 9, 11, 13, 15 ),
			array (20, 22, 16, 18, 28, 30, 24, 26, 4, 6, 0, 2, 12, 14, 8, 10, 23, 21, 19, 17, 31, 29, 27, 25, 7, 5, 3, 1, 15, 13, 11, 9 ),
			array (22, 20, 18, 16, 30, 28, 26, 24, 6, 4, 2, 0, 14, 12, 10, 8, 21, 23, 17, 19, 29, 31, 25, 27, 5, 7, 1, 3, 13, 15, 9, 11 ),
			array (24, 26, 28, 30, 16, 18, 20, 22, 8, 10, 12, 14, 0, 2, 4, 6, 27, 25, 31, 29, 19, 17, 23, 21, 11, 9, 15, 13, 3, 1, 7, 5 ),
			array (26, 24, 30, 28, 18, 16, 22, 20, 10, 8, 14, 12, 2, 0, 6, 4, 25, 27, 29, 31, 17, 19, 21, 23, 9, 11, 13, 15, 1, 3, 5, 7 ),
			array (28, 30, 24, 26, 20, 22, 16, 18, 12, 14, 8, 10, 4, 6, 0, 2, 31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1 ),
			array (30, 28, 26, 24, 22, 20, 18, 16, 14, 12, 10, 8, 6, 4, 2, 0, 29, 31, 25, 27, 21, 23, 17, 19, 13, 15, 9, 11, 5, 7, 1, 3 ),
			array ( 3, 1, 7, 5, 11, 9, 15, 13, 19, 17, 23, 21, 27, 25, 31, 29, 0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30),
			array ( 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31, 2, 0, 6, 4, 10, 8, 14, 12, 18, 16, 22, 20, 26, 24, 30, 28),
			array ( 7, 5, 3, 1, 15, 13, 11, 9, 23, 21, 19, 17, 31, 29, 27, 25, 4, 6, 0, 2, 12, 14, 8, 10, 20, 22, 16, 18, 28, 30, 24, 26),
			array ( 5, 7, 1, 3, 13, 15, 9, 11, 21, 23, 17, 19, 29, 31, 25, 27, 6, 4, 2, 0, 14, 12, 10, 8, 22, 20, 18, 16, 30, 28, 26, 24),
			array (11, 9, 15, 13, 3, 1, 7, 5, 27, 25, 31, 29, 19, 17, 23, 21, 8, 10, 12, 14, 0, 2, 4, 6, 24, 26, 28, 30, 16, 18, 20, 22 ),
			array ( 9, 11, 13, 15, 1, 3, 5, 7, 25, 27, 29, 31, 17, 19, 21, 23, 10, 8, 14, 12, 2, 0, 6, 4, 26, 24, 30, 28, 18, 16, 22, 20),
			array (15, 13, 11, 9, 7, 5, 3, 1, 31, 29, 27, 25, 23, 21, 19, 17, 12, 14, 8, 10, 4, 6, 0, 2, 28, 30, 24, 26, 20, 22, 16, 18 ),
			array (13, 15, 9, 11, 5, 7, 1, 3, 29, 31, 25, 27, 21, 23, 17, 19, 14, 12, 10, 8, 6, 4, 2, 0, 30, 28, 26, 24, 22, 20, 18, 16 ),
			array (19, 17, 23, 21, 27, 25, 31, 29, 3, 1, 7, 5, 11, 9, 15, 13, 16, 18, 20, 22, 24, 26, 28, 30, 0, 2, 4, 6, 8, 10, 12, 14 ),
			array (17, 19, 21, 23, 25, 27, 29, 31, 1, 3, 5, 7, 9, 11, 13, 15, 18, 16, 22, 20, 26, 24, 30, 28, 2, 0, 6, 4, 10, 8, 14, 12 ),
			array (23, 21, 19, 17, 31, 29, 27, 25, 7, 5, 3, 1, 15, 13, 11, 9, 20, 22, 16, 18, 28, 30, 24, 26, 4, 6, 0, 2, 12, 14, 8, 10 ),
			array (21, 23, 17, 19, 29, 31, 25, 27, 5, 7, 1, 3, 13, 15, 9, 11, 22, 20, 18, 16, 30, 28, 26, 24, 6, 4, 2, 0, 14, 12, 10, 8 ),
			array (27, 25, 31, 29, 19, 17, 23, 21, 11, 9, 15, 13, 3, 1, 7, 5, 24, 26, 28, 30, 16, 18, 20, 22, 8, 10, 12, 14, 0, 2, 4, 6 ),
			array (25, 27, 29, 31, 17, 19, 21, 23, 9, 11, 13, 15, 1, 3, 5, 7, 26, 24, 30, 28, 18, 16, 22, 20, 10, 8, 14, 12, 2, 0, 6, 4 ),
			array (31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1, 28, 30, 24, 26, 20, 22, 16, 18, 12, 14, 8, 10, 4, 6, 0, 2 ),
			array (29, 31, 25, 27, 21, 23, 17, 19, 13, 15, 9, 11, 5, 7, 1, 3, 30, 28, 26, 24, 22, 20, 18, 16, 14, 12, 10, 8, 6, 4, 2, 0 )
		);

	for ($i=0; $i < $len; $i++) {
		$j = base32Index($code[$i]);
		if ($j == 32) {
			return '-';
			}
		$interim = $damm32Matrix[$interim][$j];
		}
	return $base32[$interim];
	}

/*
    Returns 1 if base32 number is error free, 0 if it contains an error 
    or no error check digit.
*/
function damm32Check($code) {
	global $base32;
	return (damm32Encode($code) == $base32[0]) ? 1 : 0;
	}

/*
    Prints to stdout the base32 number, the base32 check digit and the error check.
*/

if ($argc == 2 && $argv[1]) {
	printf("Base32 Number: %s\nBase32 Check Digit: %s\nError Check: %d\n", $argv[1], damm32Encode($argv[1]), damm32Check($argv[1]));
	}

else {
	printf("Usage: damm32 [base32 number]\n");
	return 0;
	}
?>