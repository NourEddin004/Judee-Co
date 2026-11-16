<?php

namespace App\Domain\Media;

use RuntimeException;

class InvalidImage extends RuntimeException
{
    public static function unreadable(): self
    {
        return new self('This file could not be read as a JPG, PNG or WEBP image.');
    }
}
