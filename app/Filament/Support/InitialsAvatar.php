<?php

namespace App\Filament\Support;

use App\Support\DesignTokens;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A round initial, drawn here instead of asking an outside avatar service (no admin names leave the site). */
class InitialsAvatar implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initial = e(Str::upper(Str::substr(Filament::getNameForDefaultAvatar($record), 0, 1)));
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="%s"/>'
            .'<text x="32" y="41" font-family="Montserrat,sans-serif" font-size="26" font-weight="700" text-anchor="middle" fill="%s">%s</text></svg>',
            DesignTokens::color('rose-soft'),
            DesignTokens::color('rose-strong'),
            $initial,
        );

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
