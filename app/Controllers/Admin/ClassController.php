<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Concerns\Flashes;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\User;
use Framework\Controller;
use Framework\Request;
use Framework\Response;
use Framework\Validator;

final class ClassController extends Controller
{
    use Flashes;

    public function index(Request $request): Response
    {
        return $this->render('admin/classes/index', [
            'title' => 'Classes',
            'classes' => SchoolClass::all(),
            'flash' => $this->flash(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->render('admin/classes/form', $this->formData());
    }

    public function store(Request $request): Response
    {
        $v = $this->validator($request);

        if ($v->fails()) {
            return $this->render('admin/classes/form', $this->formData($v->errors(), $request));
        }

        $joinCode = (string) $v->get('join_code');

        if ($joinCode !== '' && SchoolClass::joinCodeExists($joinCode)) {
            return $this->render('admin/classes/form', $this->formData(
                ['join_code' => ['Join code is already in use.']],
                $request,
            ));
        }

        $classId = SchoolClass::create(
            (string) $v->get('name'),
            $joinCode !== '' ? $joinCode : null,
        );
        $this->syncMemberships($classId, $request);
        $this->setFlash('Class created.');

        return $this->redirect('/admin/classes');
    }

    public function edit(Request $request, string $id): Response
    {
        $schoolClass = SchoolClass::find((int) $id);

        if ($schoolClass === null) {
            return Response::html('Class not found.', 404);
        }

        $classId = (int) $schoolClass['id'];

        return $this->render('admin/classes/form', [
            ...$this->formData([], null, $schoolClass),
            'teacherIds' => ClassMembership::teacherIdsForClass($classId),
            'students' => ClassMembership::studentsForClass($classId),
            'teachers' => $this->teachersList(),
            'allStudents' => $this->studentsList(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $classId = (int) $id;
        $schoolClass = SchoolClass::find($classId);

        if ($schoolClass === null) {
            return Response::html('Class not found.', 404);
        }

        $v = $this->validator($request);

        if ($v->fails()) {
            return $this->render('admin/classes/form', [
                ...$this->formData($v->errors(), $request, $schoolClass),
                'teacherIds' => $this->intList($request->input('teacher_ids', [])),
                'students' => ClassMembership::studentsForClass($classId),
                'teachers' => $this->teachersList(),
                'allStudents' => $this->studentsList(),
            ]);
        }

        $joinCode = (string) $v->get('join_code');

        if (SchoolClass::joinCodeExists($joinCode, $classId)) {
            return $this->render('admin/classes/form', [
                ...$this->formData(['join_code' => ['Join code is already in use.']], $request, $schoolClass),
                'teacherIds' => $this->intList($request->input('teacher_ids', [])),
                'students' => ClassMembership::studentsForClass($classId),
                'teachers' => $this->teachersList(),
                'allStudents' => $this->studentsList(),
            ]);
        }

        SchoolClass::update($classId, (string) $v->get('name'), $joinCode);
        $this->syncMemberships($classId, $request);
        $this->setFlash('Class updated.');

        return $this->redirect('/admin/classes/' . $classId . '/edit');
    }

    public function destroy(Request $request, string $id): Response
    {
        $classId = (int) $id;

        if (SchoolClass::find($classId) === null) {
            return Response::html('Class not found.', 404);
        }

        SchoolClass::delete($classId);
        $this->setFlash('Class deleted.');

        return $this->redirect('/admin/classes');
    }

    public function enrollStudent(Request $request, string $id): Response
    {
        $classId = (int) $id;
        $studentId = (int) $request->input('student_id', 0);

        if (SchoolClass::find($classId) === null || $studentId <= 0) {
            return Response::html('Not found.', 404);
        }

        ClassMembership::enrollStudent($classId, $studentId);
        $this->setFlash('Student enrolled.');

        return $this->redirect('/admin/classes/' . $classId . '/edit');
    }

    public function unenrollStudent(Request $request, string $id, string $userId): Response
    {
        $classId = (int) $id;
        ClassMembership::unenrollStudent($classId, (int) $userId);
        $this->setFlash('Student removed.');

        return $this->redirect('/admin/classes/' . $classId . '/edit');
    }

    private function validator(Request $request): Validator
    {
        return Validator::make($request->all(), [
            'name' => 'trim|required|max:120',
            'join_code' => 'trim|max:32',
        ], [
            'name.required' => 'Name is required.',
        ]);
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

    /** @return list<array{id: int|string, email: string, name: string, role: string}> */
    private function teachersList(): array
    {
        return array_values(array_filter(
            User::all(),
            static fn (array $u): bool => ($u['role'] ?? '') === User::ROLE_TEACHER
                || ($u['role'] ?? '') === User::ROLE_ADMIN,
        ));
    }

    /** @return list<array{id: int|string, email: string, name: string, role: string}> */
    private function studentsList(): array
    {
        return array_values(array_filter(
            User::all(),
            static fn (array $u): bool => ($u['role'] ?? '') === User::ROLE_STUDENT,
        ));
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed>|null $schoolClass
     * @return array<string, mixed>
     */
    private function formData(
        array $errors = [],
        ?Request $request = null,
        ?array $schoolClass = null,
    ): array {
        $isEdit = $schoolClass !== null;

        if ($request !== null) {
            $old = [
                'name' => (string) $request->input('name', ''),
                'join_code' => (string) $request->input('join_code', ''),
            ];
        } elseif ($isEdit) {
            $old = [
                'name' => (string) $schoolClass['name'],
                'join_code' => (string) $schoolClass['join_code'],
            ];
        } else {
            $old = ['name' => '', 'join_code' => ''];
        }

        return [
            'title' => $isEdit ? 'Edit class' : 'New class',
            'errors' => $errors,
            'old' => $old,
            'schoolClass' => $schoolClass,
            'formAction' => $isEdit ? '/admin/classes/' . $schoolClass['id'] : '/admin/classes',
            'cancelHref' => '/admin/classes',
            'teacherIds' => [],
            'students' => [],
            'teachers' => $this->teachersList(),
            'allStudents' => $this->studentsList(),
        ];
    }
}
