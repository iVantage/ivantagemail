<?php

namespace IvantageMailTest\Service;

use IvantageMail\Service\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase {

    public function testEmailMatchesDomain_EmailMatches_ReturnsTrue() {
        $email = 'foo@ivantagehealth.com';
        $domains = array(
            'ivantagehealth.com',
            'gmail.com'
        );

        $result = Utils::emailMatchesDomain($email, $domains);
        $this->assertTrue($result);
    }

    public function testEmailMatchesDomain_EmailDoesntMatches_ReturnsFalse() {
        $email = 'foo@blargusmail.com';
        $domains = array(
            'ivantagehealth.com',
            'gmail.com'
        );

        $result = Utils::emailMatchesDomain($email, $domains);
        $this->assertFalse($result);
    }

}
