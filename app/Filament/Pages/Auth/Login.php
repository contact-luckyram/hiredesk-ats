<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/** In demo mode the login form is pre-filled so visitors can just click "Sign in". */
class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (config('app.demo')) {
            $this->form->fill([
                'email' => 'admin@hiredesk.test',
                'password' => 'password',
                'remember' => true,
            ]);
        }
    }

    public function getSubheading(): string | Htmlable | null
    {
        return config('app.demo')
            ? 'Live demo: just click Sign in. Sample data resets automatically.'
            : parent::getSubheading();
    }
}
