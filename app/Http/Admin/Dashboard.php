<?php

declare(strict_types=1);

namespace App\Http\Admin;

use App\Models\Assignment;
use App\Models\SchoolClass;
use App\Models\User;
use Framework\Controller;
use Framework\Middleware\Authenticate;
use App\Middleware\RequireAdmin;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;

#[Middleware([Authenticate::class, RequireAdmin::class])]
final class Dashboard extends Controller
{
    #[Get('/admin')]
    public function __invoke(Request $request): Response
    {
        return $this->render('admin/index', [
            'title' => 'Admin',
            'userCount' => User::query()->get()->count(),
            'classCount' => SchoolClass::query()->get()->count(),
            'assignmentCount' => Assignment::query()->get()->count(),
            'flash' => $this->flash(),
        ]);
    }
}
