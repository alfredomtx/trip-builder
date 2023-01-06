<?php

if (! function_exists('create_acronym_from_words')) {
    /**
     * Returns an uppercase acronnym string from the words parameter.
     * The words can be separated by space( ), comma(,), underscore(_) and dash(=).
     * 
     * If the words has only one word, the acronym will be the first and last letter.
     * @param string $words
     * @return string
     */
    function create_acronym_from_words(String $words): string
    {
        // Create the code getting the first letter of each word from $name, and setting as uppercase
        // Delimit by multiple spaces, hyphen, underscore, comma
        $words = preg_split("/[\s,_-]+/", $words);

        $acronym = '';
        // if has only one word, get the first and last letter from the word
        if (count($words) == 1){
            $w = reset($words);
            $acronym .= substr($w, 0, 1);
            $acronym .= substr($w, strlen($w) - 1, strlen($w));
            return strtoupper($acronym);
        }

        foreach ($words as $w) {
            $acronym .= mb_substr($w, 0, 1);
        }
        return strtoupper($acronym);
    }
}


