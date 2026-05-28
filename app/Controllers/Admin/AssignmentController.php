<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Concerns\Flashes;
use App\Models\Assignment;
use App\Models\SchoolClass;
use Framework\Auth;
use Framework\Controller;
use Framework\Request;
use Framework\Response;
use Framework\Validator;

final class AssignmentController extends Controller
{
    use Flashes;

    public function index(Request $request): Response
    {
        $classFilter = (int) $request->query('class_id', 0);
        $assignments = Assignment::all();
        $classes = SchoolClass::all();

        if ($classFilter > 0) {
            $assignments = array_values(array_filter(
                $assignments,
                static fn (array $a): bool => (int) $a['class_id'] === $classFilter,
            ));
        }

        return $this->render('admin/assignments/index', [
            'title' => 'Assignments',
            'assignments' => $assignments,
            'classes' => $classes,
            'classFilter' => $classFilter,
            'flash' => $this->flash(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->render('admin/assignments/form', $this->formData());
    }

    public function store(Request $request): Response
    {
        $v = $this->validator($request);

        if ($v->fails()) {
            return $this->render('admin/assignments/form', $this->formData($v->errors(), $request));
        }

        $classId = (int) $v->get('class_id');

        if (SchoolClass::find($classId) === null) {
            return $this->render('admin/assignments/form', $this->formData(
                ['class_id' => ['Invalid class.']],
                $request,
            ));
        }

        Assignment::create(
            $classId,
            (string) $v->get('title'),
            (string) $v->get('description'),
            Assignment::normalizeDueAt((string) $v->get('due_at')),
            $this->adminId(),
        );
        $this->setFlash('Assignment created.');

        return $this->redirect('/admin/assignments');
    }

    public function edit(Request $request, string $id): Response
    {
        $assignment = Assignment::find((int) $id);

        if ($assignment === null) {
            return Response::html('Not found.', 404);
        }

        return $this->render('admin/assignments/form', $this->formData([], null, $assignment));
    }

    public function update(Request $request, string $id): Response
    {
        $aid = (int) $id;
        $assignment = Assignment::find($aid);

        if ($assignment === null) {
            return Response::html('Not found.', 404);
        }

        $v = $this->validator($request);

        if ($v->fails()) {
            return $this->render('admin/assignments/form', $this->formData($v->errors(), $request, $assignment));
        }

        $classId = (int) $v->get('class_id');
        Assignment::update(
            $aid,
            $classId,
            (string) $v->get('title'),
            (string) $v->get('description'),
            Assignment::normalizeDueAt((string) $v->get('due_at')),
        );
        $this->setFlash('Assignment updated.');

        return $this->redirect('/admin/assignments');
    }

    public function destroy(Request $request, string $id): Response
    {
        $assignment = Assignment::find((int) $id);

        if ($assignment === null) {
            return Response::html('Not found.', 404);
        }

        Assignment::delete((int) $assignment['id'], (int) $assignment['class_id']);
        $this->setFlash('Assignment deleted.');

        return $this->redirect('/admin/assignments');
    }

    private function adminId(): int
    {
        $id = Auth::id();

        if ($id === null) {
            throw new \RuntimeException('Admin required.');
        }

        return $id;
    }

    private function validator(Request $request): Validator
    {
        return Validator::make($request->all(), [
            'class_id' => 'required',
            'title' => 'trim|required|max:120',
            'description' => 'trim|max:5000',
            'due_at' => 'trim',
        ], [
            'title.required' => 'Title is required.',
            'class_id.required' => 'Class is required.',
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed>|null $assignment
     * @return array<string, mixed>
     */
    private function formData(
        array $errors = [],
        ?Request $request = null,
        ?array $assignment = null,
    ): array {
        $isEdit = $assignment !== null;

        if ($request !== null) {
            $old = [
                'class_id' => (string) $request->input('class_id', ''),
                'title' => (string) $request->input('title', ''),
                'description' => (string) $request->input('description', ''),
                'due_at' => (string) $request->input('due_at', ''),
            ];
        } elseif ($isEdit) {
            $old = [
                'class_id' => (string) $assignment['class_id'],
                'title' => (string) $assignment['title'],
                'description' => (string) $assignment['description'],
                'due_at' => $assignment['due_at'] !== null ? (string) $assignment['due_at'] : '',
            ];
        } else {
            $old = ['class_id' => '', 'title' => '', 'description' => '', 'due_at' => ''];
        }

        return [
            'title' => $isEdit ? 'Edit assignment' : 'New assignment',
            'errors' => $errors,
            'old' => $old,
            'assignment' => $assignment,
            'classes' => SchoolClass::all(),
            'formAction' => $isEdit ? '/admin/assignments/' . $assignment['id'] : '/admin/assignments',
            'cancelHref' => '/admin/assignments',
        ];
    }
}
