<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Authorization;
use App\Controllers\Concerns\Flashes;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\FileStorage;
use Framework\Request;
use Framework\Response;
use Framework\Validator;

final class SubmissionController extends Controller
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

    public function show(Request $request, string $id): Response
    {
        $submissionId = (int) $id;
        $submission = Submission::find($submissionId);

        if ($submission === null) {
            return Response::html('Not found.', 404);
        }

        return $this->render('admin/submissions/show', [
            'title' => 'Submission',
            'submission' => $submission,
            'assignment' => Assignment::find((int) $submission['assignment_id']),
            'student' => User::find((int) $submission['student_id']),
            'files' => SubmissionFile::forSubmission($submissionId),
            'errors' => [],
            'flash' => $this->flash(),
        ]);
    }

    public function grade(Request $request, string $id): Response
    {
        $submissionId = (int) $id;
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
        ]);

        if ($v->fails()) {
            return $this->render('admin/submissions/show', [
                'title' => 'Submission',
                'submission' => $submission,
                'assignment' => Assignment::find((int) $submission['assignment_id']),
                'student' => User::find((int) $submission['student_id']),
                'files' => SubmissionFile::forSubmission($submissionId),
                'errors' => $v->errors(),
                'flash' => null,
            ]);
        }

        $scoreRaw = $v->get('grade_score');
        $score = $scoreRaw === null || $scoreRaw === '' ? null : (float) $scoreRaw;
        $adminId = Auth::id() ?? 0;

        Submission::grade($submissionId, $score, (string) $v->get('grade_feedback'), $adminId);
        $this->setFlash('Grade updated (admin override).');

        return $this->redirect('/admin/submissions/' . $submissionId);
    }

    public function destroy(Request $request, string $id): Response
    {
        $submissionId = (int) $id;
        $submission = Submission::find($submissionId);

        if ($submission === null) {
            return Response::html('Not found.', 404);
        }

        SubmissionFile::deleteAllForSubmission($submissionId, $this->storage);
        Submission::delete($submissionId);
        $this->setFlash('Submission deleted.');

        return $this->redirect('/admin/assignments');
    }

    public function downloadFile(Request $request, string $id, string $fileId): Response
    {
        $submissionId = (int) $id;
        $userId = Auth::id() ?? 0;

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
}
