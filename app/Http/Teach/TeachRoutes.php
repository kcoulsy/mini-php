<?php

declare(strict_types=1);

namespace App\Http\Teach;

use App\Authorization;
use App\Data\AssignmentFormData;
use App\Data\GradeSubmissionData;
use App\Http\Concerns\HandlesUploads;
use App\Http\Middleware\RequireTeacher;
use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\Submission;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\Middleware\Authenticate;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Routing\Attributes\Prefix;
use Framework\Validation\DtoResult;

#[Prefix('/teach')]
#[Middleware([Authenticate::class, RequireTeacher::class])]
final class TeachRoutes extends Controller
{
    use HandlesUploads;

    /** @param array<string, mixed> $uploadConfig */
    public function __construct(
        \Framework\View $view,
        array $uploadConfig = [],
    ) {
        parent::__construct($view);
        $this->bootUploads($uploadConfig);
    }

    #[Get('')]
    public function index(Request $request): Response
    {
        return $this->render('teach/index', [
            'title' => 'My classes',
            'classes' => ClassMembership::classesForTeacher($this->userId()),
            'flash' => $this->flash(),
        ]);
    }

    #[Get('/classes/{schoolClass}')]
    public function showClass(Request $request, SchoolClass $schoolClass): Response
    {
        if (!$this->canManageClass($schoolClass->id)) {
            return Response::html('Class not found.', 404);
        }

        return $this->render('teach/class', [
            'title' => $schoolClass->name,
            'schoolClass' => $schoolClass,
            'assignments' => Assignment::query()->where('class_id', $schoolClass->id)->latest('id')->get(),
            'students' => ClassMembership::studentsForClass($schoolClass->id),
            'flash' => $this->flash(),
        ]);
    }

    #[Get('/classes/{schoolClass}/assignments/{assignment}/submissions')]
    public function assignmentSubmissions(Request $request, SchoolClass $schoolClass, Assignment $assignment): Response
    {
        if (!$this->canManageClass($schoolClass->id) || $assignment->classId !== $schoolClass->id) {
            return Response::html('Not found.', 404);
        }

        $submissions = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->orderBy('student_id', 'asc')
            ->get();
        $students = [];

        foreach ($submissions as $submission) {
            if (!$submission instanceof Submission) {
                continue;
            }

            $students[$submission->studentId] = User::find($submission->studentId);
        }

        return $this->render('teach/assignment_submissions', [
            'title' => $assignment->title . ' — Submissions',
            'assignment' => $assignment,
            'schoolClass' => $schoolClass,
            'submissions' => $submissions,
            'students' => $students,
            'flash' => $this->flash(),
        ]);
    }

    #[Get('/classes/{schoolClass}/assignments/create')]
    public function createAssignment(Request $request, SchoolClass $schoolClass): Response
    {
        if (!$this->canManageClass($schoolClass->id)) {
            return Response::html('Class not found.', 404);
        }

        return $this->render('teach/assignment_form', $this->assignmentFormData($schoolClass));
    }

    #[Post('/classes/{schoolClass}/assignments')]
    public function storeAssignment(Request $request, SchoolClass $schoolClass, AssignmentFormData $data): Response
    {
        if (!$this->canManageClass($schoolClass->id)) {
            return Response::html('Class not found.', 404);
        }

        Assignment::create([
            'class_id' => $schoolClass->id,
            'title' => trim($data->title),
            'description' => trim($data->description),
            'due_at' => self::normalizeDueAt($data->dueAt),
            'created_by' => $this->userId(),
        ]);
        $this->setFlash('Assignment created.');

        return $this->redirect('/teach/classes/' . $schoolClass->id);
    }

    #[Get('/classes/{schoolClass}/assignments/{assignment}/edit')]
    public function editAssignment(Request $request, SchoolClass $schoolClass, Assignment $assignment): Response
    {
        if (!$this->canManageClass($schoolClass->id) || $assignment->classId !== $schoolClass->id) {
            return Response::html('Not found.', 404);
        }

        return $this->render('teach/assignment_form', $this->assignmentFormData(
            $schoolClass,
            [],
            null,
            null,
            null,
            $assignment,
        ));
    }

    #[Post('/classes/{schoolClass}/assignments/{assignment}')]
    public function updateAssignment(
        Request $request,
        SchoolClass $schoolClass,
        Assignment $assignment,
        AssignmentFormData $data
    ): Response {
        if (!$this->canManageClass($schoolClass->id) || $assignment->classId !== $schoolClass->id) {
            return Response::html('Not found.', 404);
        }

        $assignment->update([
            'class_id' => $schoolClass->id,
            'title' => trim($data->title),
            'description' => trim($data->description),
            'due_at' => self::normalizeDueAt($data->dueAt),
        ]);
        $this->setFlash('Assignment updated.');

        return $this->redirect('/teach/classes/' . $schoolClass->id);
    }

    #[Get('/submissions/{submission}')]
    public function showSubmission(Request $request, Submission $submission): Response
    {
        if (!Authorization::canViewSubmission($this->userId(), $submission->id)) {
            return Response::html('Not found.', 404);
        }

        return $this->render('teach/submission', [
            'title' => 'Submission',
            'submission' => $submission,
            'assignment' => Assignment::find($submission->assignmentId),
            'student' => User::find($submission->studentId),
            'files' => $this->submissionFiles($submission->id),
            'canGrade' => Authorization::canGradeSubmission($this->userId(), $submission->id),
            'errors' => [],
            'flash' => $this->flash(),
        ]);
    }

    #[Post('/submissions/{submission}/grade')]
    public function gradeSubmission(Request $request, Submission $submission, GradeSubmissionData $data): Response
    {
        if (!Authorization::canGradeSubmission($this->userId(), $submission->id)) {
            return Response::html('Forbidden.', 403);
        }

        $score = $data->gradeScore === null || $data->gradeScore === ''
            ? null
            : (float) $data->gradeScore;

        $submission->grade($score, $data->gradeFeedback, $this->userId());
        $this->setFlash('Grade saved.');

        return $this->redirect('/teach/submissions/' . $submission->id);
    }

    #[Get('/submissions/{submission}/files/{fileId}')]
    public function downloadFile(Request $request, Submission $submission, int $fileId): Response
    {
        if (!Authorization::canViewSubmission($this->userId(), $submission->id)) {
            return Response::html('Not found.', 404);
        }

        $file = $this->findSubmissionFile($fileId, $submission->id);

        if ($file === null) {
            return Response::html('Not found.', 404);
        }

        return Response::download(
            $this->storage->absolutePath($file->storedPath),
            $file->originalName,
            $file->mimeType,
        );
    }

    public function onValidationFailed(Request $request, DtoResult $result): Response
    {
        $path = $request->path();

        if (preg_match('#^/teach/submissions/(\d+)/grade$#', $path, $m)) {
            $submission = Submission::find((int) $m[1]);
            if ($submission === null) {
                return Response::html('Not found.', 404);
            }

            return $this->render('teach/submission', [
                'title' => 'Submission',
                'submission' => $submission,
                'assignment' => Assignment::find($submission->assignmentId),
                'student' => User::find($submission->studentId),
                'files' => $this->submissionFiles($submission->id),
                'canGrade' => true,
                'errors' => $result->errors,
                'flash' => null,
            ]);
        }

        if (preg_match('#^/teach/classes/(\d+)/assignments(?:/(\d+))?$#', $path, $m)) {
            $schoolClass = SchoolClass::find((int) $m[1]);
            if ($schoolClass === null) {
                return Response::html('Class not found.', 404);
            }

            $assignment = isset($m[2]) ? Assignment::find((int) $m[2]) : null;

            return $this->render('teach/assignment_form', $this->assignmentFormData(
                $schoolClass,
                $result->errors,
                (string) ($result->old['title'] ?? ''),
                (string) ($result->old['description'] ?? ''),
                (string) ($result->old['due_at'] ?? ''),
                $assignment,
            ));
        }

        return Response::html('Validation failed.', 422);
    }

    private function canManageClass(int $classId): bool
    {
        return Authorization::canManageClass($this->userId(), $classId) || Auth::isAdmin();
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
    private function assignmentFormData(
        SchoolClass $schoolClass,
        array $errors = [],
        ?string $title = null,
        ?string $description = null,
        ?string $dueAt = null,
        ?Assignment $assignment = null,
    ): array {
        $isEdit = $assignment !== null;

        return [
            'title' => $isEdit ? 'Edit assignment' : 'New assignment',
            'schoolClass' => $schoolClass,
            'errors' => $errors,
            'old' => [
                'title' => $title ?? ($isEdit ? $assignment->title : ''),
                'description' => $description ?? ($isEdit ? $assignment->description : ''),
                'due_at' => $dueAt ?? ($isEdit ? (string) ($assignment->dueAt ?? '') : ''),
            ],
            'formAction' => $isEdit
                ? '/teach/classes/' . $schoolClass->id . '/assignments/' . $assignment->id
                : '/teach/classes/' . $schoolClass->id . '/assignments',
            'cancelHref' => '/teach/classes/' . $schoolClass->id,
        ];
    }
}
