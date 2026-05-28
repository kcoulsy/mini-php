<?php

declare(strict_types=1);

use App\Controllers\Admin\AssignmentController as AdminAssignmentController;
use App\Controllers\Admin\ClassController as AdminClassController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\SubmissionController as AdminSubmissionController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\StudentController;
use App\Controllers\TeacherController;
use App\Models\User;
use Framework\App;
use Framework\Auth;
use Framework\Middleware\Authenticate;
use Framework\Middleware\GuestOnly;
use Framework\Middleware\RequireAdmin;
use Framework\Middleware\RequireRole;
use Framework\Response;

/** @var App $app */
$router = $app->router();
$view = $app->view();

/** @var array<string, mixed> $authConfig */
$authConfig = $app->config('auth', []);
/** @var array<string, mixed> $uploadConfig */
$uploadConfig = $app->config('uploads', []);

$auth = new AuthController($view, is_array($authConfig) ? $authConfig : []);
$student = new StudentController($view, is_array($uploadConfig) ? $uploadConfig : []);
$teacher = new TeacherController($view, is_array($uploadConfig) ? $uploadConfig : []);
$adminDashboard = new AdminDashboardController($view);
$adminUsers = new AdminUserController($view, is_array($authConfig) ? $authConfig : []);
$adminClasses = new AdminClassController($view);
$adminAssignments = new AdminAssignmentController($view);
$adminSubmissions = new AdminSubmissionController($view, is_array($uploadConfig) ? $uploadConfig : []);

$authMiddleware = [Authenticate::class];
$guestMiddleware = [GuestOnly::class];
$studentMiddleware = array_merge($authMiddleware, [RequireRole::allowing(User::ROLE_STUDENT)]);
$teachMiddleware = array_merge($authMiddleware, [RequireRole::allowing(User::ROLE_TEACHER, User::ROLE_ADMIN)]);
$adminMiddleware = array_merge($authMiddleware, [RequireAdmin::class]);

$router->get('/', fn () => Auth::check()
    ? Response::redirect(Auth::homePath())
    : Response::redirect('/login'));

$router->get('/login', fn ($request) => $auth->showLogin($request), $guestMiddleware);
$router->post('/login', fn ($request) => $auth->login($request), $guestMiddleware);
$router->get('/register', fn ($request) => $auth->showRegister($request), $guestMiddleware);
$router->post('/register', fn ($request) => $auth->register($request), $guestMiddleware);
$router->post('/logout', fn ($request) => $auth->logout($request), $authMiddleware);

$router->get('/student', fn ($request) => $student->index($request), $studentMiddleware);
$router->post('/student/classes/join', fn ($request) => $student->join($request), $studentMiddleware);
$router->get('/student/classes/{id}', fn ($request, $id) => $student->showClass($request, $id), $studentMiddleware);
$router->get('/student/assignments/{id}', fn ($request, $id) => $student->showAssignment($request, $id), $studentMiddleware);
$router->post('/student/assignments/{id}', fn ($request, $id) => $student->submitAssignment($request, $id), $studentMiddleware);
$router->get('/student/submissions/{id}/files/{fileId}', fn ($request, $id, $fileId) => $student->downloadFile($request, $id, $fileId), $studentMiddleware);

$router->get('/teach', fn ($request) => $teacher->index($request), $teachMiddleware);
$router->get('/teach/classes/{id}', fn ($request, $id) => $teacher->showClass($request, $id), $teachMiddleware);
$router->get('/teach/classes/{classId}/assignments/create', fn ($request, $classId) => $teacher->createAssignment($request, $classId), $teachMiddleware);
$router->post('/teach/classes/{classId}/assignments', fn ($request, $classId) => $teacher->storeAssignment($request, $classId), $teachMiddleware);
$router->get('/teach/classes/{classId}/assignments/{id}/edit', fn ($request, $classId, $id) => $teacher->editAssignment($request, $classId, $id), $teachMiddleware);
$router->post('/teach/classes/{classId}/assignments/{id}', fn ($request, $classId, $id) => $teacher->updateAssignment($request, $classId, $id), $teachMiddleware);
$router->get('/teach/classes/{classId}/assignments/{id}/submissions', fn ($request, $classId, $id) => $teacher->assignmentSubmissions($request, $classId, $id), $teachMiddleware);
$router->get('/teach/submissions/{id}', fn ($request, $id) => $teacher->showSubmission($request, $id), $teachMiddleware);
$router->post('/teach/submissions/{id}/grade', fn ($request, $id) => $teacher->gradeSubmission($request, $id), $teachMiddleware);
$router->get('/teach/submissions/{id}/files/{fileId}', fn ($request, $id, $fileId) => $teacher->downloadFile($request, $id, $fileId), $teachMiddleware);

$router->get('/admin', fn ($request) => $adminDashboard->index($request), $adminMiddleware);
$router->get('/admin/users', fn ($request) => $adminUsers->index($request), $adminMiddleware);
$router->get('/admin/users/create', fn ($request) => $adminUsers->create($request), $adminMiddleware);
$router->post('/admin/users', fn ($request) => $adminUsers->store($request), $adminMiddleware);
$router->get('/admin/users/{id}/edit', fn ($request, $id) => $adminUsers->edit($request, $id), $adminMiddleware);
$router->post('/admin/users/{id}', fn ($request, $id) => $adminUsers->update($request, $id), $adminMiddleware);
$router->post('/admin/users/{id}/delete', fn ($request, $id) => $adminUsers->destroy($request, $id), $adminMiddleware);

$router->get('/admin/classes', fn ($request) => $adminClasses->index($request), $adminMiddleware);
$router->get('/admin/classes/create', fn ($request) => $adminClasses->create($request), $adminMiddleware);
$router->post('/admin/classes', fn ($request) => $adminClasses->store($request), $adminMiddleware);
$router->get('/admin/classes/{id}/edit', fn ($request, $id) => $adminClasses->edit($request, $id), $adminMiddleware);
$router->post('/admin/classes/{id}', fn ($request, $id) => $adminClasses->update($request, $id), $adminMiddleware);
$router->post('/admin/classes/{id}/delete', fn ($request, $id) => $adminClasses->destroy($request, $id), $adminMiddleware);
$router->post('/admin/classes/{id}/enroll', fn ($request, $id) => $adminClasses->enrollStudent($request, $id), $adminMiddleware);
$router->post('/admin/classes/{id}/students/{userId}/remove', fn ($request, $id, $userId) => $adminClasses->unenrollStudent($request, $id, $userId), $adminMiddleware);

$router->get('/admin/assignments', fn ($request) => $adminAssignments->index($request), $adminMiddleware);
$router->get('/admin/assignments/create', fn ($request) => $adminAssignments->create($request), $adminMiddleware);
$router->post('/admin/assignments', fn ($request) => $adminAssignments->store($request), $adminMiddleware);
$router->get('/admin/assignments/{id}/edit', fn ($request, $id) => $adminAssignments->edit($request, $id), $adminMiddleware);
$router->post('/admin/assignments/{id}', fn ($request, $id) => $adminAssignments->update($request, $id), $adminMiddleware);
$router->post('/admin/assignments/{id}/delete', fn ($request, $id) => $adminAssignments->destroy($request, $id), $adminMiddleware);

$router->get('/admin/submissions/{id}', fn ($request, $id) => $adminSubmissions->show($request, $id), $adminMiddleware);
$router->post('/admin/submissions/{id}/grade', fn ($request, $id) => $adminSubmissions->grade($request, $id), $adminMiddleware);
$router->post('/admin/submissions/{id}/delete', fn ($request, $id) => $adminSubmissions->destroy($request, $id), $adminMiddleware);
$router->get('/admin/submissions/{id}/files/{fileId}', fn ($request, $id, $fileId) => $adminSubmissions->downloadFile($request, $id, $fileId), $adminMiddleware);
