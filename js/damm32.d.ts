/** The base32 alphabet: 23456789abcdefghjkmnpqrstuvwxyz* (input is case-insensitive). */
export const ALPHABET: string;

/** Returned by checkDigit() when the input contains a character outside the alphabet. */
export const INVALID: '-';

/** Returns the value (0-31) of a base32 character, or -1 if it is not in the alphabet. */
export function value(char: string): number;

/** Returns the base32 check digit for a string, or INVALID for characters outside the alphabet. */
export function checkDigit(code: string): string;

/** Returns true if the last character is a valid Damm check digit for the characters before it. */
export function isValid(code: string): boolean;
