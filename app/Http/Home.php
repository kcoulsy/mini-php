<?php

declare(strict_types=1);

namespace App\Http;

use App\CurrentUser;
use Framework\Auth;
use Framework\Controller;
use Framework\Response;
use Framework\Routing\Attributes\Get;

final class Home extends Controller
{
    #[Get('/')]
    public function __invoke(): Response
    {
        if (Auth::check()) {
            return $this->redirect(CurrentUser::homePath());
        }

        return $this->redirect('/login');
    }
}
