<?php

declare(strict_types=1);

namespace App\Http\Student;

use App\Authorization;
use App\Data\JoinClassData;
use App\Http\Concerns\HandlesUploads;
use App\Http\Middleware\RequireStudent;
use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\Submission;
use Framework\Middleware\Authenticate;
use Framework\Model\Collection;
use Framework\Controller;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Routing\Attributes\Prefix;
use Framework\Validation\DtoResult;

#[Prefix('/student')]
#[Middleware([Authenticate::class, RequireStudent::class])]
final class StudentRoutes extends Controller
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
        return $this->render('student/index', [
            'title' => 'My classes',
            'classes' => ClassMembership::classesForStudent($this->userId()),
            'flash' => $this->flash(),
        ]);
    }

    #[Post('/classes/join')]
    public function join(Request $request, JoinClassData $data): Response
    {
        $schoolClass = SchoolClass::findBy('join_code', strtoupper(trim($data->joinCode)));

        if (!$schoolClass instanceof SchoolClass) {
            $this->setFlash('Invalid join code.');

            return $this->redirect('/student');
        }

        ClassMembership::enrollStudent($schoolClass->id, $this->userId());
        $this->setFlash('Joined class: ' . $schoolClass->name);

        return $this->redirect('/student/classes/' . $schoolClass->id);
    }

    #[Get('/classes/{schoolClass}')]
    public function showClass(Request $request, SchoolClass $schoolClass): Response
    {
        $userId = $this->userId();

        if (!Authorization::canAccessClass($userId, $schoolClass->id)) {
            return Response::html('Class not found.', 404);
        }

        $assignments = Assignment::query()->where('class_id', $schoolClass->id)->latest('id')->get();
        $submissions = [];

        foreach ($assignments as $assignment) {
            if (!$assignment instanceof Assignment) {
                continue;
            }

            $submission = Submission::query()
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $userId)
                ->first();
            $submissions[$assignment->id] = $submission instanceof Submission ? $submission : null;
        }

        return $this->render('student/class', [
            'title' => $schoolClass->name,
            'schoolClass' => $schoolClass,
            'assignments' => $assignments,
            'submissions' => $submissions,
            'flash' => $this->flash(),
        ]);
    }

    #[Get('/assignments/{assignment}')]
    public function showAssignment(Request $request, Assignment $assignment): Response
    {
        if (!ClassMembership::isStudent($assignment->classId, $this->userId())) {
            return Response::html('Assignment not found.', 404);
        }

        $submission = $this->studentSubmission($assignment->id, $this->userId());
        $files = $submission !== null
            ? $this->submissionFiles($submission->id)
            : new Collection();

        return $this->render('student/assignment', [
            'title' => $assignment->title,
            'assignment' => $assignment,
            'submission' => $submission,
            'files' => $files,
            'canEdit' => Authorization::canEditSubmissionForAssignment($this->userId(), $assignment->id),
            'errors' => [],
            'flash' => $this->flash(),
        ]);
    }

    #[Post('/assignments/{assignment}')]
    public function submitAssignment(Request $request, Assignment $assignment): Response
    {
        $userId = $this->userId();

        if (!ClassMembership::isStudent($assignment->classId, $userId)) {
            return Response::html('Assignment not found.', 404);
        }

        if (!Authorization::canEditSubmissionForAssignment($userId, $assignment->id)) {
            $this->setFlash('Submissions are closed for this assignment.');

            return $this->redirect('/student/assignments/' . $assignment->id);
        }

        $uploadErrors = $this->validateUploads($request);
        $files = $request->files('attachments');

        if ($files === [] && $uploadErrors === []) {
            $existing = $this->studentSubmission($assignment->id, $userId);
            $existingFiles = $existing !== null
                ? $this->submissionFiles($existing->id)
                : new Collection();

            if ($existing === null || $existingFiles->isEmpty()) {
                return $this->render('student/assignment', [
                    'title' => $assignment->title,
                    'assignment' => $assignment,
                    'submission' => $existing,
                    'files' => $existingFiles,
                    'canEdit' => true,
                    'errors' => ['attachments' => ['Please upload at least one file.']],
                    'flash' => null,
                ]);
            }
        }

        if ($uploadErrors !== []) {
            $existing = $this->studentSubmission($assignment->id, $userId);
            $existingFiles = $existing !== null
                ? $this->submissionFiles($existing->id)
                : new Collection();

            return $this->render('student/assignment', [
                'title' => $assignment->title,
                'assignment' => $assignment,
                'submission' => $existing,
                'files' => $existingFiles,
                'canEdit' => true,
                'errors' => ['attachments' => $uploadErrors],
                'flash' => null,
            ]);
        }

        $submission = Submission::upsertForStudent($assignment->id, $userId);
        $this->removeSubmissionFiles($submission->id, $this->removedFileIds($request));

        $directory = "{$assignment->classId}/{$assignment->id}/{$userId}";
        $fileErrors = $this->storeSubmissionFiles($submission->id, $files, $directory);

        if ($fileErrors !== []) {
            $currentSubmission = Submission::find($submission->id);

            return $this->render('student/assignment', [
                'title' => $assignment->title,
                'assignment' => $assignment,
                'submission' => $currentSubmission,
                'files' => $this->submissionFiles($submission->id),
                'canEdit' => true,
                'errors' => ['attachments' => $fileErrors],
                'flash' => null,
            ]);
        }

        $this->setFlash('Submission saved.');

        return $this->redirect('/student/assignments/' . $assignment->id);
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
        $this->setFlash('Could not join class. Check the join code.');

        return $this->redirect('/student');
    }

    private function studentSubmission(int $assignmentId, int $studentId): ?Submission
    {
        $submission = Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('student_id', $studentId)
            ->first();

        return $submission instanceof Submission ? $submission : null;
    }
}
