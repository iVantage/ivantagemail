<?php

namespace IvantageMail\Service;

/**
 * Collection of utility functions for email related actions.
 *
 * @package IvantageMail
 * @copyright 2015 iVantage Health Analytics, Inc.
 */
class Utils {

    /**
     * Determines whether an email address matches one of
     * the given domains.
     *
     * @param  {string} $email   Email address
     * @param  {array}  $domains Array of email domains of the format "gmail.com"
     * @return {boolean}
     */
    public static function emailMatchesDomain($email, $domains) {
        $emailRegexString = str_replace('.', '\.', implode('|', $domains));
        return preg_match("#@$emailRegexString$#", $email) === 1;
    }

}
