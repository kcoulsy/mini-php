<?php

declare(strict_types=1);

namespace App\Http\Admin;

use App\Data\ClassFormData;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\User;
use Framework\Controller;
use Framework\Middleware\Authenticate;
use App\Middleware\RequireAdmin;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Routing\Attributes\Prefix;
use Framework\Validation\ValidationRedirect;

#[Prefix('/admin')]
#[Middleware([Authenticate::class, RequireAdmin::class])]
final class Classes extends Controller
{
    #[Get('/classes')]
    public function index(Request $request): Response
    {
        return $this->render('admin/classes/index', [
            'title' => 'Classes',
            'classes' => SchoolClass::query()->orderBy('name', 'asc')->get(),
            'flash' => $this->flash(),
        ]);
    }

    #[Get('/classes/create')]
    public function create(Request $request): Response
    {
        return $this->render('admin/classes/form', $this->formData());
    }

    #[Post('/classes')]
    public function store(Request $request, ClassFormData $data): Response
    {
        if ($data->joinCode !== '' && SchoolClass::joinCodeExists($data->joinCode)) {
            ValidationRedirect::storeRequestErrors($request, ['join_code' => ['Join code is already in use.']]);

            return $this->redirect('/admin/classes/create');
        }

        $joinCode = $data->joinCode !== ''
            ? strtoupper(trim($data->joinCode))
            : SchoolClass::generateJoinCode();

        $schoolClass = SchoolClass::create([
            'name' => trim($data->name),
            'join_code' => $joinCode,
        ]);
        $this->syncMemberships($schoolClass->id, $request);
        $this->setFlash('Class created.');

        return $this->redirect('/admin/classes');
    }

    #[Get('/classes/{schoolClass}/edit')]
    public function edit(Request $request, SchoolClass $schoolClass): Response
    {
        return $this->render('admin/classes/form', $this->formData($schoolClass));
    }

    #[Post('/classes/{schoolClass}')]
    public function update(Request $request, SchoolClass $schoolClass, ClassFormData $data): Response
    {
        if (SchoolClass::joinCodeExists($data->joinCode, $schoolClass->id)) {
            ValidationRedirect::storeRequestErrors($request, ['join_code' => ['Join code is already in use.']]);

            return $this->redirect('/admin/classes/' . $schoolClass->id . '/edit');
        }

        $schoolClass->update([
            'name' => trim($data->name),
            'join_code' => strtoupper(trim($data->joinCode)),
        ]);
        $this->syncMemberships($schoolClass->id, $request);
        $this->setFlash('Class updated.');

        return $this->redirect('/admin/classes/' . $schoolClass->id . '/edit');
    }

    #[Post('/classes/{schoolClass}/delete')]
    public function destroy(Request $request, SchoolClass $schoolClass): Response
    {
        $schoolClass->delete();
        $this->setFlash('Class deleted.');

        return $this->redirect('/admin/classes');
    }

    #[Post('/classes/{schoolClass}/enroll')]
    public function enrollStudent(Request $request, SchoolClass $schoolClass): Response
    {
        $studentId = (int) $request->input('student_id', 0);

        if ($studentId <= 0) {
            return Response::html('Not found.', 404);
        }

        ClassMembership::enrollStudent($schoolClass->id, $studentId);
        $this->setFlash('Student enrolled.');

        return $this->redirect('/admin/classes/' . $schoolClass->id . '/edit');
    }

    #[Post('/classes/{schoolClass}/students/{userId}/remove')]
    public function unenrollStudent(Request $request, SchoolClass $schoolClass, int $userId): Response
    {
        ClassMembership::unenrollStudent($schoolClass->id, $userId);
        $this->setFlash('Student removed.');

        return $this->redirect('/admin/classes/' . $schoolClass->id . '/edit');
    }

    private function syncMemberships(int $classId, Request $request): void
    {
        ClassMembership::syncTeachers($classId, $this->intList($request->input('teacher_ids', [])));
    }

    /** @return list<int> */
    private function intList(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            }
        }

        return $ids;
    }

    /** @return list<User> */
    private function teachersList(): array
    {
        $teachers = [];

        foreach (User::query()->orderBy('id', 'asc')->get() as $user) {
            if (
                $user instanceof User
                && ($user->role === User::ROLE_TEACHER || $user->role === User::ROLE_ADMIN)
            ) {
                $teachers[] = $user;
            }
        }

        return $teachers;
    }

    /** @return list<User> */
    private function studentsList(): array
    {
        $students = [];

        foreach (User::query()->orderBy('id', 'asc')->get() as $user) {
            if ($user instanceof User && $user->role === User::ROLE_STUDENT) {
                $students[] = $user;
            }
        }

        return $students;
    }

    /**
     * @param array<string, list<string>> $errors
     * @return array<string, mixed>
     */
    private function formData(?SchoolClass $schoolClass = null, array $errors = []): array
    {
        $isEdit = $schoolClass !== null;
        $sessionOld = $this->validationOld();
        $sessionErrors = $this->validationErrors();

        if ($errors === [] && $sessionErrors !== []) {
            $errors = $sessionErrors;
        }

        if ($sessionOld !== []) {
            $old = [
                'name' => (string) ($sessionOld['name'] ?? ''),
                'join_code' => (string) ($sessionOld['join_code'] ?? ''),
            ];
        } elseif ($isEdit) {
            $old = [
                'name' => $schoolClass->name,
                'join_code' => $schoolClass->joinCode,
            ];
        } else {
            $old = ['name' => '', 'join_code' => ''];
        }

        return [
            'title' => $isEdit ? 'Edit class' : 'New class',
            'errors' => $errors,
            'old' => $old,
            'schoolClass' => $schoolClass,
            'formAction' => $isEdit ? '/admin/classes/' . $schoolClass->id : '/admin/classes',
            'cancelHref' => '/admin/classes',
            'teacherIds' => isset($sessionOld['teacher_ids'])
                ? $this->intList($sessionOld['teacher_ids'])
                : ($isEdit ? ClassMembership::teacherIdsForClass($schoolClass->id) : []),
            'students' => $isEdit ? ClassMembership::studentsForClass($schoolClass->id) : [],
            'teachers' => $this->teachersList(),
            'allStudents' => $this->studentsList(),
        ];
    }
}
