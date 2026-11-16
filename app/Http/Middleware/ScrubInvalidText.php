<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

/**
 * Bytes that are not valid UTF-8 become "?" in every typed-in value, so they can neither break a
 * page nor be refused by the database with an error (REQ-SEC-35).
 */
class ScrubInvalidText extends TransformsRequest
{
    protected function transform($key, $value)
    {
        return is_string($value) ? mb_scrub($value, 'UTF-8') : $value;
    }
}
