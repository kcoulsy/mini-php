<?php

declare(strict_types=1);

namespace App\Http;

use Framework\Auth;
use Framework\Controller;
use Framework\Response;
use Framework\Routing\Attributes\Get;

final class HomeRedirect extends Controller
{
    #[Get('/')]
    public function __invoke(): Response
    {
        if (Auth::check()) {
            return $this->redirect(Auth::homePath());
        }

        return $this->redirect('/login');
    }
}
