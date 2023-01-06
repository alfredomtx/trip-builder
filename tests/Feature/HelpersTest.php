<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    /**
     * Assert that the helper function works as expected, returnin a 2 letter
     * long acronym for 1 words, and more than 1 for words with more letters
     *
     * @return void
     */
    public function test_create_acronym_from_words_with_one_word(){
        // Act
        $acronym = create_acronym_from_words("Montreal");

        // Assert
        $this->assertEquals("ML", $acronym);
    }

    public function test_create_acronym_from_words_with_many_words(){
        // Act 1
        $acronym = create_acronym_from_words("New York");

        // Assert 1
        $this->assertEquals("NY", $acronym);

        // Act 2
        $acronym = create_acronym_from_words("Newfoundland and Labrador");

        // Assert 3
        $this->assertEquals("NAL", $acronym);
    }
}
