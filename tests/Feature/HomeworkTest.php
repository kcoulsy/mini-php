<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassMembership;
use App\Models\Submission;
use App\Models\User;
use Framework\UploadedFile;
use Tests\Support\ApplicationTestCase;

final class HomeworkTest extends ApplicationTestCase
{
    private function fakeUpload(string $name = 'work.jpg'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hw-upload-');
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////2wBDAf//////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
            true,
        );
        file_put_contents($path, $jpeg);

        return UploadedFile::fake($path, $name, 'image/jpeg', filesize($path));
    }

    public function testStudentJoinsClassAndSubmits(): void
    {
        $teacherId = $this->createTeacher();
        $class = $this->createClassWithTeacher($teacherId);
        $assignmentId = $this->createAssignmentForClass($class['id'], $teacherId);

        $studentId = $this->createStudent('s@example.com');
        $this->actingAs($studentId);

        $join = $this->post('/student/classes/join', ['join_code' => $class['join_code']]);
        $this->assertRedirect($join, '/student/classes/' . $class['id']);

        $submit = $this->postMultipart('/student/assignments/' . $assignmentId, [], [
            'attachments' => [$this->fakeUpload()],
        ]);
        $this->assertRedirect($submit, '/student/assignments/' . $assignmentId);

        $submission = Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('student_id', $studentId)
            ->first();
        $this->assertNotNull($submission);
    }

    public function testTeacherGradesSubmission(): void
    {
        $teacherId = $this->createTeacher();
        $class = $this->createClassWithTeacher($teacherId);
        $assignmentId = $this->createAssignmentForClass($class['id'], $teacherId);
        $studentId = $this->createStudent();

        ClassMembership::enrollStudent($class['id'], $studentId);
        $this->actingAs($studentId);
        $this->postMultipart('/student/assignments/' . $assignmentId, [], [
            'attachments' => [$this->fakeUpload('hw.jpg')],
        ]);

        $submission = Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('student_id', $studentId)
            ->first();
        $this->assertNotNull($submission);

        $this->actingAs($teacherId);
        $grade = $this->post('/teach/submissions/' . $submission->id . '/grade', [
            'grade_score' => '88',
            'grade_feedback' => 'Good work.',
        ]);
        $this->assertRedirect($grade, '/teach/submissions/' . $submission->id);

        $updated = Submission::find($submission->id);
        $this->assertNotNull($updated);
        $this->assertEquals(88.0, (float) $updated->gradeScore);
    }

    public function testStudentCannotAccessTeachRoutes(): void
    {
        $this->actingAs($this->createStudent());

        $response = $this->get('/teach');

        $this->assertEquals(403, $response->status());
    }

    public function testAdminCreatesTeacher(): void
    {
        $this->actingAs($this->createAdmin());

        $response = $this->post('/admin/users', [
            'email' => 'newteacher@example.com',
            'name' => 'New Teacher',
            'role' => User::ROLE_TEACHER,
            'password' => 'password123',
        ]);

        $this->assertRedirect($response, '/admin/users');
        $this->assertNotNull(User::findBy('email', mb_strtolower(trim('newteacher@example.com'))));
    }

    public function testCrossStudentCannotViewOtherClass(): void
    {
        $teacherId = $this->createTeacher();
        $class = $this->createClassWithTeacher($teacherId);

        $this->actingAs($this->createStudent('other@example.com'));

        $response = $this->get('/student/classes/' . $class['id']);

        $this->assertEquals(404, $response->status());
    }
}
