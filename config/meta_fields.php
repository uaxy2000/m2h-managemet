<?php

/*
 * Maps Meta Lead Form raw question text → internal display label.
 * Keys must be the result of normalizing the raw key with:
 *   mb_strtolower(str_replace(['İ', 'I'], 'i', $rawKey), 'UTF-8')
 * Add a new entry here whenever a new custom question field is discovered.
 */

return [

    // Wealth / investment capacity questions
    'eu farkli programlar sunabiliriz'
        => 'Wealth Level',

    // Italy Golden Visa specific questions
    'bu program, önce itayla\'da oturum izni verir, ardından 12 ay içerisinde minimum 250.000 euro veya üstü yatırım yapmayı zorunlu kılar.'
        => '€250k Position',

    'italya altin vize programini daha once duydunuz mu'
        => 'Italy GV Heard Before',

];
