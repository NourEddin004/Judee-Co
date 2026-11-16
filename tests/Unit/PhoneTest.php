<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** The checkout phone rule on the server (REQ-CHK-21, REQ-CHK-22, REQ-DMO-56, 57, 70, Appendix D.8.3). */
class PhoneTest extends TestCase
{
    protected bool $seed = false;

    #[Test]
    public function d_8_3_each_country_has_its_digits_and_its_example_is_valid(): void
    {
        $digits = collect(config('store.phone_countries'))->mapWithKeys(fn (array $country) => [$country['code'] => $country['digits']])->all();

        $this->assertSame([
            'JO' => [9], 'PS' => [9], 'SA' => [9], 'AE' => [9], 'KW' => [8], 'QA' => [8], 'BH' => [8], 'OM' => [8],
            'IQ' => [10], 'LB' => [7, 8], 'SY' => [9], 'EG' => [10], 'TR' => [10], 'GB' => [10], 'US' => [10], 'DE' => [10, 11],
            'YE' => [9], 'LY' => [9], 'TN' => [8], 'DZ' => [9], 'MA' => [9], 'SD' => [9], 'FR' => [9], 'IT' => [10], 'ES' => [9], 'NL' => [9], 'SE' => [9], 'AU' => [9],
        ], $digits);

        foreach (config('store.phone_countries') as $country) {
            $this->assertTrue(Phone::isValid($country['placeholder'], $country), "the example for {$country['code']}");
        }
    }

    #[Test]
    public function req_chk_22_req_dmo_56_a_number_needs_exactly_the_countrys_digits_not_counting_a_0_at_the_start(): void
    {
        $jordan = Phone::country('JO');

        $this->assertSame('+962 791234567', Phone::international('0791234567', $jordan));
        $this->assertSame('+962 791234567', Phone::international('791234567', $jordan));
        $this->assertTrue(Phone::isValid('0791234567', $jordan));
        $this->assertFalse(Phone::isValid('79123456', $jordan), 'one digit short');
        $this->assertFalse(Phone::isValid('7912345678', $jordan), 'one digit too many');
        $this->assertFalse(Phone::isValid('00791234567', $jordan), 'only one 0 at the start is not counted');

        $this->assertTrue(Phone::isValid('50012345', Phone::country('KW')));
        $this->assertFalse(Phone::isValid('500123456', Phone::country('KW')));

        $lebanon = Phone::country('LB');
        $this->assertTrue(Phone::isValid('3123456', $lebanon));
        $this->assertTrue(Phone::isValid('71123456', $lebanon));
        $this->assertTrue(Phone::isValid('03 123 456', $lebanon));
        $this->assertFalse(Phone::isValid('711234567', $lebanon));

        $germany = Phone::country('DE');
        $this->assertTrue(Phone::isValid('1512345678', $germany));
        $this->assertTrue(Phone::isValid('015123456789', $germany));
        $this->assertFalse(Phone::isValid('151234567', $germany));
    }

    #[Test]
    public function req_chk_21_req_dmo_90_only_digits_count_and_arabic_indic_digits_are_0_to_9(): void
    {
        $jordan = Phone::country('JO');

        $this->assertSame('791234567', Phone::digits('79a1-23 4(567)', $jordan));
        $this->assertSame('0791234567', Phone::digits('٠٧٩١٢٣٤٥٦٧', $jordan));
        $this->assertSame('+962 791234567', Phone::international('٠٧٩١٢٣٤٥٦٧', $jordan));
        $this->assertSame('+962 791234567', Phone::international('۷۹۱۲۳۴۵۶۷', $jordan), 'Persian digits');
    }

    #[Test]
    public function req_dmo_57_a_too_long_number_starting_with_the_dial_code_loses_it_once(): void
    {
        $jordan = Phone::country('JO');

        $this->assertSame('791234567', Phone::digits('+962 79 123 4567', $jordan));
        $this->assertSame('791234567', Phone::digits('00962 79 123 4567', $jordan));
        $this->assertSame('791234567', Phone::digits('962791234567', $jordan));
        $this->assertSame('+962 791234567', Phone::international('+962 079 123 4567', $jordan), 'with the 0 kept after the code');
        $this->assertSame('962791234', Phone::digits('962791234', $jordan), 'not too long, so kept');
        $this->assertFalse(Phone::isValid('0096279123', $jordan), '00 and the code are not taken as a 0 at the start');

        // A Kuwaiti number may itself start with 965; only a longer one loses the code, and only once.
        $kuwait = Phone::country('KW');
        $this->assertSame('96512345', Phone::digits('96512345', $kuwait));
        $this->assertSame('96512345', Phone::digits('+965 9651 2345', $kuwait));

        $this->assertSame('+1 2015550123', Phone::international('+1 (201) 555-0123', Phone::country('US')));
        $this->assertSame('+44 7400123456', Phone::international('+44 (0) 7400 123456', Phone::country('GB')));
    }

    #[Test]
    public function req_dmo_70_the_message_names_the_countrys_digits_and_dial_code(): void
    {
        $this->assertSame('Please enter 9 digits after +962.', Phone::invalidMessage(Phone::country('JO')));
        $this->assertSame('Please enter 8 digits after +965.', Phone::invalidMessage(Phone::country('KW')));
        $this->assertSame('Please enter 7 or 8 digits after +961.', Phone::invalidMessage(Phone::country('LB')));
        $this->assertSame('Please enter 10 or 11 digits after +49.', Phone::invalidMessage(Phone::country('DE')));

        app()->setLocale('ar');
        $this->assertSame("يرجى إدخال 9 أرقام بعد \u{2066}+962\u{2069}.", Phone::invalidMessage(Phone::country('JO')));
        $this->assertSame("يرجى إدخال 7 أو 8 أرقام بعد \u{2066}+961\u{2069}.", Phone::invalidMessage(Phone::country('LB')));
        $this->assertSame("يرجى إدخال 10 أو 11 رقمًا بعد \u{2066}+49\u{2069}.", Phone::invalidMessage(Phone::country('DE')));
    }

    #[Test]
    public function an_unknown_country_falls_back_to_jordan(): void
    {
        $this->assertSame('962', Phone::country('XX')['dial']);
        $this->assertSame('962', Phone::country(null)['dial']);
        $this->assertSame('962', Phone::country(['KW'])['dial']);
    }
}
