<?php

use App\Models\User;
use Framework\Auth;
use Framework\View;

/** @var User|null $user */
$navUser = $user ?? Auth::user();

if (!Auth::check() || !($navUser instanceof User)) {
    return;
}

$role = (string) ($navUser->role ?? User::ROLE_STUDENT);

if ($role === User::ROLE_ADMIN): ?>
    <a href="/admin">Dashboard</a>
    <a href="/admin/users">Users</a>
    <a href="/admin/classes">Classes</a>
    <a href="/admin/assignments">Assignments</a>
<?php elseif ($role === User::ROLE_TEACHER): ?>
    <a href="/teach">My classes</a>
<?php else: ?>
    <a href="/student">My classes</a>
<?php endif; ?>
