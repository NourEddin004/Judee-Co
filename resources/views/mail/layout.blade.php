{{-- Order emails: one simple column in JUDE's colours, inline styles only, so every mail app shows it the same. --}}
@use('App\Support\DesignTokens', 'Token')
@php
    $rtl = app()->getLocale() === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:{{ Token::color('blush-50') }};color:{{ Token::color('ink') }};font-family:Montserrat,Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ Token::color('blush-50') }};">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:{{ Token::color('white') }};border:1px solid {{ Token::color('blush-100') }};border-radius:12px;">
                    <tr>
                        <td style="padding:24px 24px 0;font-family:Merriweather,Georgia,serif;font-size:24px;letter-spacing:0.12em;color:{{ Token::color('plum') }};text-align:{{ $rtl ? 'right' : 'left' }};" dir="ltr">JUDE</td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px 24px;text-align:{{ $rtl ? 'right' : 'left' }};">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
