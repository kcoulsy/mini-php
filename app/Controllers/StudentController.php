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
use Framework\Auth;
use Framework\Controller;
use Framework\FileStorage;
use Framework\Request;
use Framework\Response;
use Framework\UploadValidator;
use Framework\Validator;

final class StudentController extends Controller
{
    use Flashes;

    private FileStorage $storage;

    /** @param array<string, mixed> $uploadConfig */
    public function __construct(
        \Framework\View $view,
        private readonly array $uploadConfig = [],
    ) {
        parent::__construct($view);
        $this->storage = FileStorage::fromConfig($uploadConfig);
    }

    public function index(Request $request): Response
    {
        $classes = SchoolClass::forStudent($this->userId());

        return $this->render('student/index', [
            'title' => 'My classes',
            'classes' => $classes,
            'flash' => $this->flash(),
        ]);
    }

    public function join(Request $request): Response
    {
        $v = Validator::make($request->all(), [
            'join_code' => 'trim|required|max:32',
        ], [
            'join_code.required' => 'Join code is required.',
        ]);

        if ($v->fails()) {
            $this->setFlash('Could not join class. Check the join code.');

            return $this->redirect('/student');
        }

        $schoolClass = SchoolClass::findByJoinCode((string) $v->get('join_code'));

        if ($schoolClass === null) {
            $this->setFlash('Invalid join code.');

            return $this->redirect('/student');
        }

        ClassMembership::enrollStudent((int) $schoolClass['id'], $this->userId());
        $this->setFlash('Joined class: ' . $schoolClass['name']);

        return $this->redirect('/student/classes/' . $schoolClass['id']);
    }

    public function showClass(Request $request, string $id): Response
    {
        $classId = (int) $id;
        $userId = $this->userId();

        if (!Authorization::canAccessClass($userId, $classId)) {
            return Response::html('Class not found.', 404);
        }

        $schoolClass = SchoolClass::find($classId);

        if ($schoolClass === null) {
            return Response::html('Class not found.', 404);
        }

        $assignments = Assignment::forClass($classId);
        $submissions = [];

        foreach ($assignments as $assignment) {
            $sub = Submission::findForStudent((int) $assignment['id'], $userId);
            $submissions[(int) $assignment['id']] = $sub;
        }

        return $this->render('student/class', [
            'title' => (string) $schoolClass['name'],
            'schoolClass' => $schoolClass,
            'assignments' => $assignments,
            'submissions' => $submissions,
            'flash' => $this->flash(),
        ]);
    }

    public function showAssignment(Request $request, string $id): Response
    {
        $assignmentId = (int) $id;
        $assignment = Assignment::find($assignmentId);

        if ($assignment === null || !ClassMembership::isStudent((int) $assignment['class_id'], $this->userId())) {
            return Response::html('Assignment not found.', 404);
        }

        $submission = Submission::findForStudent($assignmentId, $this->userId());
        $canEdit = Authorization::canEditSubmissionForAssignment($this->userId(), $assignmentId);
        $files = $submission !== null ? SubmissionFile::forSubmission((int) $submission['id']) : [];

        return $this->render('student/assignment', [
            'title' => (string) $assignment['title'],
            'assignment' => $assignment,
            'submission' => $submission,
            'files' => $files,
            'canEdit' => $canEdit,
            'errors' => [],
            'flash' => $this->flash(),
        ]);
    }

    public function submitAssignment(Request $request, string $id): Response
    {
        $assignmentId = (int) $id;
        $userId = $this->userId();
        $assignment = Assignment::find($assignmentId);

        if ($assignment === null || !ClassMembership::isStudent((int) $assignment['class_id'], $userId)) {
            return Response::html('Assignment not found.', 404);
        }

        if (!Authorization::canEditSubmissionForAssignment($userId, $assignmentId)) {
            $this->setFlash('Submissions are closed for this assignment.');

            return $this->redirect('/student/assignments/' . $assignmentId);
        }

        $uploadErrors = $this->validateUploads($request);
        $files = $request->files('attachments');

        if ($files === [] && $uploadErrors === []) {
            $existing = Submission::findForStudent($assignmentId, $userId);

            if ($existing === null || SubmissionFile::forSubmission((int) $existing['id']) === []) {
                return $this->render('student/assignment', [
                    'title' => (string) $assignment['title'],
                    'assignment' => $assignment,
                    'submission' => $existing,
                    'files' => $existing !== null ? SubmissionFile::forSubmission((int) $existing['id']) : [],
                    'canEdit' => true,
                    'errors' => ['attachments' => ['Please upload at least one file.']],
                    'flash' => null,
                ]);
            }
        }

        if ($uploadErrors !== []) {
            $existing = Submission::findForStudent($assignmentId, $userId);

            return $this->render('student/assignment', [
                'title' => (string) $assignment['title'],
                'assignment' => $assignment,
                'submission' => $existing,
                'files' => $existing !== null ? SubmissionFile::forSubmission((int) $existing['id']) : [],
                'canEdit' => true,
                'errors' => ['attachments' => $uploadErrors],
                'flash' => null,
            ]);
        }

        $classId = (int) $assignment['class_id'];
        $submissionId = Submission::upsertForStudent($assignmentId, $userId);
        SubmissionFile::deleteIds($submissionId, $this->removedFileIds($request), $this->storage);

        $directory = "{$classId}/{$assignmentId}/{$userId}";
        $fileErrors = SubmissionFile::createMany(
            $submissionId,
            $files,
            $directory,
            $this->uploadConfig,
            $this->storage,
        );

        if ($fileErrors !== []) {
            return $this->render('student/assignment', [
                'title' => (string) $assignment['title'],
                'assignment' => $assignment,
                'submission' => Submission::find($submissionId),
                'files' => SubmissionFile::forSubmission($submissionId),
                'canEdit' => true,
                'errors' => ['attachments' => $fileErrors],
                'flash' => null,
            ]);
        }

        $this->setFlash('Submission saved.');

        return $this->redirect('/student/assignments/' . $assignmentId);
    }

    public function downloadFile(Request $request, string $id, string $fileId): Response
    {
        $submissionId = (int) $id;
        $userId = $this->userId();

        if (!Authorization::canViewSubmission($userId, $submissionId)) {
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

    /** @return list<string> */
    private function validateUploads(Request $request): array
    {
        $files = $request->files('attachments');

        if ($files === []) {
            return [];
        }

        return UploadValidator::validateMany($this->uploadConfig, $files);
    }

    /** @return list<int> */
    private function removedFileIds(Request $request): array
    {
        $raw = $request->input('removed_attachment_ids', []);

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
}
