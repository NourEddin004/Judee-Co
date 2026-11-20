<?php

/*
| Fixed storefront numbers from the SRS. Values the owner changes live in the settings table.
*/
return [
    'max_quantity' => 9,            // REQ-DMO-28, REQ-DMO-55
    'toast_ms' => 2800,             // REQ-DMO-37
    'back_to_top_offset' => 600,    // REQ-DMO-52
    'storage_keys' => [
        // v2: bag lines hold product colour ids since products hold their colours (SRS 1.2).
        'bag' => 'jude.bag.v2',
        'currency' => 'jude.currency',
    ],

    // Phone countries, in the order of Appendix D.8.3 (REQ-DMO-58). Jordan is the default. Digits are
    // the lengths a number may have after the dial code, not counting a 0 at the start (REQ-CHK-22).
    'phone_countries' => [
        ['code' => 'JO', 'dial' => '962', 'digits' => [9], 'flag' => '🇯🇴', 'placeholder' => '7 9012 3456'],
        ['code' => 'PS', 'dial' => '970', 'digits' => [9], 'flag' => '🇵🇸', 'placeholder' => '59 123 4567'],
        ['code' => 'SA', 'dial' => '966', 'digits' => [9], 'flag' => '🇸🇦', 'placeholder' => '51 234 5678'],
        ['code' => 'AE', 'dial' => '971', 'digits' => [9], 'flag' => '🇦🇪', 'placeholder' => '50 123 4567'],
        ['code' => 'KW', 'dial' => '965', 'digits' => [8], 'flag' => '🇰🇼', 'placeholder' => '500 12345'],
        ['code' => 'QA', 'dial' => '974', 'digits' => [8], 'flag' => '🇶🇦', 'placeholder' => '3312 3456'],
        ['code' => 'BH', 'dial' => '973', 'digits' => [8], 'flag' => '🇧🇭', 'placeholder' => '3600 1234'],
        ['code' => 'OM', 'dial' => '968', 'digits' => [8], 'flag' => '🇴🇲', 'placeholder' => '9212 3456'],
        ['code' => 'IQ', 'dial' => '964', 'digits' => [10], 'flag' => '🇮🇶', 'placeholder' => '791 234 5678'],
        ['code' => 'LB', 'dial' => '961', 'digits' => [7, 8], 'flag' => '🇱🇧', 'placeholder' => '71 123 456'],
        ['code' => 'SY', 'dial' => '963', 'digits' => [9], 'flag' => '🇸🇾', 'placeholder' => '944 567 890'],
        ['code' => 'EG', 'dial' => '20', 'digits' => [10], 'flag' => '🇪🇬', 'placeholder' => '100 123 4567'],
        ['code' => 'TR', 'dial' => '90', 'digits' => [10], 'flag' => '🇹🇷', 'placeholder' => '501 234 5678'],
        ['code' => 'GB', 'dial' => '44', 'digits' => [10], 'flag' => '🇬🇧', 'placeholder' => '7400 123456'],
        ['code' => 'US', 'dial' => '1', 'digits' => [10], 'flag' => '🇺🇸', 'placeholder' => '201 555 0123'],
        ['code' => 'DE', 'dial' => '49', 'digits' => [10, 11], 'flag' => '🇩🇪', 'placeholder' => '1512 3456789'],
        // SRS 1.4: 12 more countries (D.8.3 rows 17 to 28).
        ['code' => 'YE', 'dial' => '967', 'digits' => [9], 'flag' => '🇾🇪', 'placeholder' => '712 345 678'],
        ['code' => 'LY', 'dial' => '218', 'digits' => [9], 'flag' => '🇱🇾', 'placeholder' => '91 234 5678'],
        ['code' => 'TN', 'dial' => '216', 'digits' => [8], 'flag' => '🇹🇳', 'placeholder' => '20 123 456'],
        ['code' => 'DZ', 'dial' => '213', 'digits' => [9], 'flag' => '🇩🇿', 'placeholder' => '551 23 45 67'],
        ['code' => 'MA', 'dial' => '212', 'digits' => [9], 'flag' => '🇲🇦', 'placeholder' => '650 123 456'],
        ['code' => 'SD', 'dial' => '249', 'digits' => [9], 'flag' => '🇸🇩', 'placeholder' => '91 123 1234'],
        ['code' => 'FR', 'dial' => '33', 'digits' => [9], 'flag' => '🇫🇷', 'placeholder' => '6 12 34 56 78'],
        ['code' => 'IT', 'dial' => '39', 'digits' => [10], 'flag' => '🇮🇹', 'placeholder' => '312 345 6789'],
        ['code' => 'ES', 'dial' => '34', 'digits' => [9], 'flag' => '🇪🇸', 'placeholder' => '612 34 56 78'],
        ['code' => 'NL', 'dial' => '31', 'digits' => [9], 'flag' => '🇳🇱', 'placeholder' => '6 1234 5678'],
        ['code' => 'SE', 'dial' => '46', 'digits' => [9], 'flag' => '🇸🇪', 'placeholder' => '70 123 45 67'],
        ['code' => 'AU', 'dial' => '61', 'digits' => [9], 'flag' => '🇦🇺', 'placeholder' => '412 345 678'],
    ],

    // Gulf delivery countries (SRS 1.4, REQ-CHK-26): one delivery row each, 25 JD per order to start
    // (REQ-CHK-30); the owner changes the fee or switches a country off under Delivery.
    'gulf_countries' => [
        ['key' => 'saudi-arabia', 'country' => 'SA', 'name_en' => 'Saudi Arabia', 'name_ar' => 'السعودية'],
        ['key' => 'uae', 'country' => 'AE', 'name_en' => 'United Arab Emirates', 'name_ar' => 'الإمارات'],
        ['key' => 'kuwait', 'country' => 'KW', 'name_en' => 'Kuwait', 'name_ar' => 'الكويت'],
        ['key' => 'qatar', 'country' => 'QA', 'name_en' => 'Qatar', 'name_ar' => 'قطر'],
        ['key' => 'bahrain', 'country' => 'BH', 'name_en' => 'Bahrain', 'name_ar' => 'البحرين'],
        ['key' => 'oman', 'country' => 'OM', 'name_en' => 'Oman', 'name_ar' => 'عُمان'],
    ],

    // Free delivery (2026-11-20): a Jordan order whose subtotal reaches this many JD is delivered free.
    // Gulf countries keep their own fee, since the courier there costs JUDE far more.
    'free_delivery_from' => 50,

    // At most this many photos per product colour: 1 main photo and up to 3 more (REQ-CMS-10, REQ-STR-30).
    'max_photos_per_color' => 4,

    // Uploaded images (REQ-SEC-19, REQ-SEC-31, REQ-SEC-32, REQ-DB-15).
    'images' => [
        'max_kb' => 5120,
        'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_width' => 1600,
        // Resized copies the browser picks from (srcset): 200 for thumbnails, 600 for a full-width
        // photo on most phones, 400 and 800 for cards and larger screens (REQ-PRF-02).
        'variant_widths' => [200, 400, 600, 800],
        // WebP quality of every copy: side by side it looks the same as 82, and the files are a quarter smaller.
        'quality' => 75,
    ],

    // Hero rack fitting (REQ-RCK-13 to REQ-RCK-16). The demo garments are 760 px tall
    // with the hanger hook at the top centre of the image.
    'rack' => [
        'height' => 760,
        'variant_heights' => [380],
        'size_min' => 0.6,
        'size_max' => 1.4,
        'size_step' => 0.05,
    ],

    // The owner is emailed when the server's disk is this full, at most once a day (REQ-CST-10).
    'disk_alert_percent' => 80,
];
