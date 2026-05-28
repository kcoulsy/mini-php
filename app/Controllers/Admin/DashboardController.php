<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Concerns\Flashes;
use App\Models\Assignment;
use App\Models\SchoolClass;
use App\Models\User;
use Framework\Controller;
use Framework\Request;
use Framework\Response;

final class DashboardController extends Controller
{
    use Flashes;

    public function index(Request $request): Response
    {
        return $this->render('admin/index', [
            'title' => 'Admin',
            'userCount' => count(User::all()),
            'classCount' => count(SchoolClass::all()),
            'assignmentCount' => count(Assignment::all()),
            'flash' => $this->flash(),
        ]);
    }
}
