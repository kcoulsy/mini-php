<?php

declare(strict_types=1);

namespace App\Http\Admin;

use App\Data\AdminAssignmentFormData;
use App\Models\Assignment;
use App\Models\SchoolClass;
use Framework\Auth;
use Framework\Controller;
use Framework\Middleware\Authenticate;
use Framework\Middleware\RequireAdmin;
use Framework\Model\Collection;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Routing\Attributes\Prefix;
use Framework\Validation\DtoResult;

#[Prefix('/admin')]
#[Middleware([Authenticate::class, RequireAdmin::class])]
final class AssignmentRoutes extends Controller
{
    #[Get('/assignments')]
    public function index(Request $request): Response
    {
        $classFilter = (int) $request->query('class_id', 0);
        $assignments = Assignment::query()->orderBy('class_id', 'asc')->latest('id')->get();

        if ($classFilter > 0) {
            $filtered = [];
            foreach ($assignments as $assignment) {
                if ($assignment instanceof Assignment && $assignment->classId === $classFilter) {
                    $filtered[] = $assignment;
                }
            }
            $assignments = new Collection($filtered);
        }

        return $this->render('admin/assignments/index', [
            'title' => 'Assignments',
            'assignments' => $assignments,
            'classes' => SchoolClass::query()->orderBy('name', 'asc')->get(),
            'classFilter' => $classFilter,
            'flash' => $this->flash(),
        ]);
    }

    #[Get('/assignments/create')]
    public function create(Request $request): Response
    {
        return $this->render('admin/assignments/form', $this->formData());
    }

    #[Post('/assignments')]
    public function store(Request $request, AdminAssignmentFormData $data): Response
    {
        $classId = (int) $data->classId;
        if (SchoolClass::find($classId) === null) {
            return $this->render('admin/assignments/form', $this->formData(
                ['class_id' => ['Invalid class.']],
                $request,
            ));
        }

        Assignment::create([
            'class_id' => $classId,
            'title' => trim($data->title),
            'description' => trim($data->description),
            'due_at' => self::normalizeDueAt($data->dueAt),
            'created_by' => $this->adminId(),
        ]);
        $this->setFlash('Assignment created.');

        return $this->redirect('/admin/assignments');
    }

    #[Get('/assignments/{assignment}/edit')]
    public function edit(Request $request, Assignment $assignment): Response
    {
        return $this->render('admin/assignments/form', $this->formData([], null, $assignment));
    }

    #[Post('/assignments/{assignment}')]
    public function update(Request $request, Assignment $assignment, AdminAssignmentFormData $data): Response
    {
        $assignment->update([
            'class_id' => (int) $data->classId,
            'title' => trim($data->title),
            'description' => trim($data->description),
            'due_at' => self::normalizeDueAt($data->dueAt),
        ]);
        $this->setFlash('Assignment updated.');

        return $this->redirect('/admin/assignments');
    }

    #[Post('/assignments/{assignment}/delete')]
    public function destroy(Request $request, Assignment $assignment): Response
    {
        $assignment->delete();
        $this->setFlash('Assignment deleted.');

        return $this->redirect('/admin/assignments');
    }

    public function onValidationFailed(Request $request, DtoResult $result): Response
    {
        $path = $request->path();

        if ($path === '/admin/assignments') {
            return $this->render('admin/assignments/form', $this->formData($result->errors, $request));
        }

        if (preg_match('#^/admin/assignments/(\d+)$#', $path, $m)) {
            $assignment = Assignment::find((int) $m[1]);
            if ($assignment === null) {
                return Response::html('Not found.', 404);
            }

            return $this->render('admin/assignments/form', $this->formData($result->errors, $request, $assignment));
        }

        return Response::html('Validation failed.', 422);
    }

    private function adminId(): int
    {
        $id = Auth::id();

        if ($id === null) {
            throw new \RuntimeException('Admin required.');
        }

        return $id;
    }

    private static function normalizeDueAt(?string $dueAt): ?string
    {
        if ($dueAt === null || trim($dueAt) === '') {
            return null;
        }

        return trim($dueAt);
    }

    /**
     * @param array<string, list<string>> $errors
     * @return array<string, mixed>
     */
    private function formData(
        array $errors = [],
        ?Request $request = null,
        ?Assignment $assignment = null,
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
                'class_id' => (string) $assignment->classId,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'due_at' => (string) ($assignment->dueAt ?? ''),
            ];
        } else {
            $old = ['class_id' => '', 'title' => '', 'description' => '', 'due_at' => ''];
        }

        return [
            'title' => $isEdit ? 'Edit assignment' : 'New assignment',
            'errors' => $errors,
            'old' => $old,
            'assignment' => $assignment,
            'classes' => SchoolClass::query()->orderBy('name', 'asc')->get(),
            'formAction' => $isEdit ? '/admin/assignments/' . $assignment->id : '/admin/assignments',
            'cancelHref' => '/admin/assignments',
        ];
    }
}
