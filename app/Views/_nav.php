<?php

use App\Models\User;
use Framework\Auth;
use Framework\View;

/** @var array{role: string}|null $user */
if (!Auth::check() || !is_array($user ?? Auth::user())) {
    return;
}

$role = (string) (($user ?? Auth::user())['role'] ?? User::ROLE_STUDENT);

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
