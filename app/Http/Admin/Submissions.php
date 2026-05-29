<?php

declare(strict_types=1);

namespace App\Http\Admin;

use App\Authorization;
use App\Data\GradeSubmissionData;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\Database;
use Framework\FileStorage;
use Framework\Middleware\Authenticate;
use App\Middleware\RequireAdmin;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Routing\Attributes\Prefix;
use Framework\Validation\DtoResult;

#[Prefix('/admin')]
#[Middleware([Authenticate::class, RequireAdmin::class])]
final class Submissions extends Controller
{
    private FileStorage $storage;

    /** @param array<string, mixed> $uploadConfig */
    public function __construct(
        \Framework\View $view,
        array $uploadConfig = [],
    ) {
        parent::__construct($view);
        $this->storage = FileStorage::fromConfig($uploadConfig);
    }

    #[Get('/submissions/{submission}')]
    public function show(Request $request, Submission $submission): Response
    {
        $submission->load('files');

        return $this->render('admin/submissions/show', [
            'title' => 'Submission',
            'submission' => $submission,
            'assignment' => Assignment::find($submission->assignmentId),
            'student' => User::find($submission->studentId),
            'files' => $submission->files,
            'errors' => [],
            'flash' => $this->flash(),
        ]);
    }

    #[Post('/submissions/{submission}/grade')]
    public function grade(Request $request, Submission $submission, GradeSubmissionData $data): Response
    {
        $score = $data->gradeScore === null || $data->gradeScore === ''
            ? null
            : (float) $data->gradeScore;
        $adminId = Auth::id() ?? 0;

        $submission->grade($score, $data->gradeFeedback, $adminId);
        $this->setFlash('Grade updated (admin override).');

        return $this->redirect('/admin/submissions/' . $submission->id);
    }

    #[Post('/submissions/{submission}/delete')]
    public function destroy(Request $request, Submission $submission): Response
    {
        $submission->load('files');

        foreach ($submission->files as $attachment) {
            if ($attachment instanceof SubmissionFile) {
                $this->storage->delete($attachment->storedPath);
            }
        }

        $stmt = Database::pdo()->prepare(
            'DELETE FROM submission_files WHERE submission_id = :submission_id'
        );
        $stmt->execute(['submission_id' => $submission->id]);

        $submission->delete();
        $this->setFlash('Submission deleted.');

        return $this->redirect('/admin/assignments');
    }

    #[Get('/submissions/{submission}/files/{fileId}')]
    public function downloadFile(Request $request, Submission $submission, int $fileId): Response
    {
        $userId = Auth::id() ?? 0;
        if (!Authorization::canViewSubmission($userId, $submission->id)) {
            return Response::html('Not found.', 404);
        }

        $file = SubmissionFile::query()
            ->where('id', $fileId)
            ->where('submission_id', $submission->id)
            ->first();

        if (!$file instanceof SubmissionFile) {
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
        if (!preg_match('#^/admin/submissions/(\d+)/grade$#', $request->path(), $m)) {
            return Response::html('Validation failed.', 422);
        }

        $submission = Submission::find((int) $m[1]);
        if ($submission === null) {
            return Response::html('Not found.', 404);
        }

        $submission->load('files');

        return $this->render('admin/submissions/show', [
            'title' => 'Submission',
            'submission' => $submission,
            'assignment' => Assignment::find($submission->assignmentId),
            'student' => User::find($submission->studentId),
            'files' => $submission->files,
            'errors' => $result->errors,
            'flash' => null,
        ]);
    }
}
