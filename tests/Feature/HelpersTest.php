<?php

namespace Tests\Feature;

use DateTime;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function PHPUnit\Framework\assertEquals;

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

    public function test_convert_time_to_utc_from_timezone(){
        // Arrange
        $montrealTime = date("H:i", strtotime("01:00 PM"));
        $vancouverTime = date("H:i", strtotime("03:00 PM"));

        // Act
        $montrealTimeUtc = convert_time_to_utc_from_timezone($montrealTime, 'America/Montreal');
        $vancouverTimeUtc = convert_time_to_utc_from_timezone($vancouverTime, 'America/Vancouver');
        
        // Assert
        assertEquals($montrealTimeUtc, '18:00');
        assertEquals($vancouverTimeUtc, '23:00');
    }

}

