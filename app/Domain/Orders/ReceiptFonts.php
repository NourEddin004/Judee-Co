<?php

namespace App\Domain\Orders;

use Mpdf\Language\LanguageToFont;

/**
 * The font the receipt switches to for a run of text in another script, such as an Arabic address
 * on an English receipt: Arabic script is set in IBM Plex Sans Arabic, as on the site. Other
 * scripts keep the library's own choice.
 */
final class ReceiptFonts extends LanguageToFont
{
    private const ARABIC_SCRIPT = ['ar', 'ara', 'fa', 'fas', 'ur', 'urd', 'ps', 'pus'];

    public function getLanguageOptions($llcc, $adobeCJK)
    {
        $tags = explode('-', strtolower((string) $llcc));

        if (in_array($tags[0], self::ARABIC_SCRIPT, true) || in_array('arab', $tags, true)) {
            return [false, 'plexarabic'];
        }

        return parent::getLanguageOptions($llcc, $adobeCJK);
    }
}
