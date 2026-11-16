<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;

/**
 * Email plus password (REQ-USR-01), 5 tries a minute per IP (REQ-SEC-01).
 * There is no "remember me": an admin session always ends after 2 idle hours (REQ-SEC-22).
 */
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
        ]);
    }
}
