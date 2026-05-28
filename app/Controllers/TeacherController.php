<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Authorization;
use App\Controllers\Concerns\Flashes;
use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\FileStorage;
use Framework\Request;
use Framework\Response;
use Framework\Validator;

final class TeacherController extends Controller
{
    use Flashes;

    private FileStorage $storage;

    public function __construct(
        \Framework\View $view,
        private readonly array $uploadConfig = [],
    ) {
        parent::__construct($view);
        $this->storage = FileStorage::fromConfig($uploadConfig);
    }

    public function index(Request $request): Response
    {
        $classes = SchoolClass::forTeacher($this->userId());

        return $this->render('teach/index', [
            'title' => 'My classes',
            'classes' => $classes,
            'flash' => $this->flash(),
        ]);
    }

    public function showClass(Request $request, string $id): Response
    {
        $classId = (int) $id;
        $userId = $this->userId();

        if (!Authorization::canManageClass($userId, $classId) && !Auth::isAdmin()) {
            return Response::html('Class not found.', 404);
        }

        $schoolClass = SchoolClass::find($classId);

        if ($schoolClass === null) {
            return Response::html('Class not found.', 404);
        }

        return $this->render('teach/class', [
            'title' => (string) $schoolClass['name'],
            'schoolClass' => $schoolClass,
            'assignments' => Assignment::forClass($classId),
            'students' => ClassMembership::studentsForClass($classId),
            'flash' => $this->flash(),
        ]);
    }

    public function assignmentSubmissions(Request $request, string $classId, string $id): Response
    {
        $cid = (int) $classId;
        $aid = (int) $id;
        $userId = $this->userId();

        if (!Authorization::canManageClass($userId, $cid) && !Auth::isAdmin()) {
            return Response::html('Not found.', 404);
        }

        $assignment = Assignment::findForClass($aid, $cid);

        if ($assignment === null) {
            return Response::html('Not found.', 404);
        }

        $submissions = Submission::forAssignment($aid);
        $students = [];

        foreach ($submissions as $submission) {
            $student = User::find((int) $submission['student_id']);
            $students[(int) $submission['student_id']] = $student;
        }

        return $this->render('teach/assignment_submissions', [
            'title' => (string) $assignment['title'] . ' — Submissions',
            'assignment' => $assignment,
            'schoolClass' => SchoolClass::find($cid),
            'submissions' => $submissions,
            'students' => $students,
            'flash' => $this->flash(),
        ]);
    }

    public function createAssignment(Request $request, string $classId): Response
    {
        $cid = (int) $classId;

        if (!Authorization::canManageClass($this->userId(), $cid) && !Auth::isAdmin()) {
            return Response::html('Class not found.', 404);
        }

        $schoolClass = SchoolClass::find($cid);

        if ($schoolClass === null) {
            return Response::html('Class not found.', 404);
        }

        return $this->render('teach/assignment_form', $this->assignmentFormData($schoolClass));
    }

    public function storeAssignment(Request $request, string $classId): Response
    {
        $cid = (int) $classId;

        if (!Authorization::canManageClass($this->userId(), $cid) && !Auth::isAdmin()) {
            return Response::html('Class not found.', 404);
        }

        $schoolClass = SchoolClass::find($cid);

        if ($schoolClass === null) {
            return Response::html('Class not found.', 404);
        }

        $v = $this->assignmentValidator($request);

        if ($v->fails()) {
            return $this->render('teach/assignment_form', $this->assignmentFormData(
                $schoolClass,
                $v->errors(),
                (string) $v->get('title'),
                (string) $v->get('description'),
                (string) $v->get('due_at'),
            ));
        }

        Assignment::create(
            $cid,
            (string) $v->get('title'),
            (string) $v->get('description'),
            Assignment::normalizeDueAt((string) $v->get('due_at')),
            $this->userId(),
        );
        $this->setFlash('Assignment created.');

        return $this->redirect('/teach/classes/' . $cid);
    }

    public function editAssignment(Request $request, string $classId, string $id): Response
    {
        $cid = (int) $classId;
        $aid = (int) $id;

        if (!Authorization::canManageClass($this->userId(), $cid) && !Auth::isAdmin()) {
            return Response::html('Not found.', 404);
        }

        $schoolClass = SchoolClass::find($cid);
        $assignment = Assignment::findForClass($aid, $cid);

        if ($schoolClass === null || $assignment === null) {
            return Response::html('Not found.', 404);
        }

        return $this->render('teach/assignment_form', $this->assignmentFormData($schoolClass, [], null, null, null, $assignment));
    }

    public function updateAssignment(Request $request, string $classId, string $id): Response
    {
        $cid = (int) $classId;
        $aid = (int) $id;

        if (!Authorization::canManageClass($this->userId(), $cid) && !Auth::isAdmin()) {
            return Response::html('Not found.', 404);
        }

        $schoolClass = SchoolClass::find($cid);
        $assignment = Assignment::findForClass($aid, $cid);

        if ($schoolClass === null || $assignment === null) {
            return Response::html('Not found.', 404);
        }

        $v = $this->assignmentValidator($request);

        if ($v->fails()) {
            return $this->render('teach/assignment_form', $this->assignmentFormData(
                $schoolClass,
                $v->errors(),
                (string) $v->get('title'),
                (string) $v->get('description'),
                (string) $v->get('due_at'),
                $assignment,
            ));
        }

        Assignment::update(
            $aid,
            $cid,
            (string) $v->get('title'),
            (string) $v->get('description'),
            Assignment::normalizeDueAt((string) $v->get('due_at')),
        );
        $this->setFlash('Assignment updated.');

        return $this->redirect('/teach/classes/' . $cid);
    }

    public function showSubmission(Request $request, string $id): Response
    {
        $submissionId = (int) $id;
        $userId = $this->userId();

        if (!Authorization::canViewSubmission($userId, $submissionId)) {
            return Response::html('Not found.', 404);
        }

        $submission = Submission::find($submissionId);

        if ($submission === null) {
            return Response::html('Not found.', 404);
        }

        $assignment = Assignment::find((int) $submission['assignment_id']);
        $student = User::find((int) $submission['student_id']);
        $canGrade = Authorization::canGradeSubmission($userId, $submissionId);

        return $this->render('teach/submission', [
            'title' => 'Submission',
            'submission' => $submission,
            'assignment' => $assignment,
            'student' => $student,
            'files' => SubmissionFile::forSubmission($submissionId),
            'canGrade' => $canGrade,
            'errors' => [],
            'flash' => $this->flash(),
        ]);
    }

    public function gradeSubmission(Request $request, string $id): Response
    {
        $submissionId = (int) $id;
        $userId = $this->userId();

        if (!Authorization::canGradeSubmission($userId, $submissionId)) {
            return Response::html('Forbidden.', 403);
        }

        $submission = Submission::find($submissionId);

        if ($submission === null) {
            return Response::html('Not found.', 404);
        }

        $v = Validator::make($request->all(), [
            'grade_score' => [
                'trim',
                static function (mixed $value): ?string {
                    if ($value === null || $value === '') {
                        return null;
                    }

                    if (!is_numeric($value)) {
                        return 'Score must be a number.';
                    }

                    $score = (float) $value;

                    if ($score < 0 || $score > 100) {
                        return 'Score must be between 0 and 100.';
                    }

                    return null;
                },
            ],
            'grade_feedback' => 'trim|max:5000',
        ], [
            'grade_feedback.max' => 'Feedback must be 5000 characters or fewer.',
        ]);

        if ($v->fails()) {
            $assignment = Assignment::find((int) $submission['assignment_id']);
            $student = User::find((int) $submission['student_id']);

            return $this->render('teach/submission', [
                'title' => 'Submission',
                'submission' => $submission,
                'assignment' => $assignment,
                'student' => $student,
                'files' => SubmissionFile::forSubmission($submissionId),
                'canGrade' => true,
                'errors' => $v->errors(),
                'flash' => null,
            ]);
        }

        $scoreRaw = $v->get('grade_score');
        $score = $scoreRaw === null || $scoreRaw === '' ? null : (float) $scoreRaw;

        Submission::grade(
            $submissionId,
            $score,
            (string) $v->get('grade_feedback'),
            $userId,
        );
        $this->setFlash('Grade saved.');

        return $this->redirect('/teach/submissions/' . $submissionId);
    }

    public function downloadFile(Request $request, string $id, string $fileId): Response
    {
        $submissionId = (int) $id;

        if (!Authorization::canViewSubmission($this->userId(), $submissionId)) {
            return Response::html('Not found.', 404);
        }

        $file = SubmissionFile::findForViewer((int) $fileId, $submissionId);

        if ($file === null) {
            return Response::html('Not found.', 404);
        }

        return Response::download(
            $this->storage->absolutePath((string) $file['stored_path']),
            (string) $file['original_name'],
            (string) $file['mime_type'],
        );
    }

    private function userId(): int
    {
        $id = Auth::id();

        if ($id === null) {
            throw new \RuntimeException('Authenticated user required.');
        }

        return $id;
    }

    private function assignmentValidator(Request $request): Validator
    {
        return Validator::make($request->all(), [
            'title' => 'trim|required|max:120',
            'description' => 'trim|max:5000',
            'due_at' => 'trim',
        ], [
            'title.required' => 'Title is required.',
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed>|null $assignment
     * @return array<string, mixed>
     */
    private function assignmentFormData(
        array $schoolClass,
        array $errors = [],
        ?string $title = null,
        ?string $description = null,
        ?string $dueAt = null,
        ?array $assignment = null,
    ): array {
        $classId = (int) $schoolClass['id'];
        $isEdit = $assignment !== null;
        $aid = $isEdit ? (int) $assignment['id'] : null;

        return [
            'title' => $isEdit ? 'Edit assignment' : 'New assignment',
            'schoolClass' => $schoolClass,
            'errors' => $errors,
            'old' => [
                'title' => $title ?? ($isEdit ? (string) $assignment['title'] : ''),
                'description' => $description ?? ($isEdit ? (string) $assignment['description'] : ''),
                'due_at' => $dueAt ?? ($isEdit && $assignment['due_at'] !== null ? (string) $assignment['due_at'] : ''),
            ],
            'formAction' => $isEdit
                ? '/teach/classes/' . $classId . '/assignments/' . $aid
                : '/teach/classes/' . $classId . '/assignments',
            'cancelHref' => '/teach/classes/' . $classId,
        ];
    }
}
