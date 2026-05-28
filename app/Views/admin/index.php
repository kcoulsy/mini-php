<section class="page-head">
    <h1>Admin dashboard</h1>
</section>

<ul class="stat-list">
    <li><strong><?= (int) $userCount ?></strong> users</li>
    <li><strong><?= (int) $classCount ?></strong> classes</li>
    <li><strong><?= (int) $assignmentCount ?></strong> assignments</li>
</ul>

<p>
    <a href="/admin/users" class="btn btn-primary">Manage users</a>
    <a href="/admin/classes" class="btn btn-primary">Manage classes</a>
    <a href="/admin/assignments" class="btn btn-primary">Manage assignments</a>
</p>
