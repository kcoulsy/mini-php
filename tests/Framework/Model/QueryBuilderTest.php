<?php

declare(strict_types=1);

namespace Tests\Framework\Model;

use App\Models\ClassStudent;
use App\Models\SchoolClass;
use InvalidArgumentException;
use Tests\Framework\Model\Fixtures\TestUser;
use Tests\Support\DatabaseTestCase;

final class QueryBuilderTest extends DatabaseTestCase
{
    public function testCountAndExists(): void
    {
        TestUser::create([
            'email' => 'one@example.com',
            'password' => 'hash',
            'name' => 'One',
            'role' => 'student',
        ]);
        TestUser::create([
            'email' => 'two@example.com',
            'password' => 'hash',
            'name' => 'Two',
            'role' => 'teacher',
        ]);

        $this->assertEquals(2, TestUser::query()->count());
        $this->assertTrue(TestUser::query()->where('role', 'teacher')->exists());
        $this->assertFalse(TestUser::query()->where('role', 'admin')->exists());
    }

    public function testWhereIn(): void
    {
        $first = TestUser::create([
            'email' => 'a@example.com',
            'password' => 'hash',
            'name' => 'A',
            'role' => 'student',
        ]);
        TestUser::create([
            'email' => 'b@example.com',
            'password' => 'hash',
            'name' => 'B',
            'role' => 'student',
        ]);

        $found = TestUser::query()->whereIn('id', [$first->id])->get();
        $this->assertEquals(1, $found->count());
        $this->assertEquals('a@example.com', $found->first()?->email);
    }

    public function testEmptyWhereInMatchesNothing(): void
    {
        TestUser::create([
            'email' => 'a@example.com',
            'password' => 'hash',
            'name' => 'A',
            'role' => 'student',
        ]);

        $this->assertEquals(0, TestUser::query()->whereIn('id', [])->count());
        $this->assertFalse(TestUser::query()->whereIn('id', [])->exists());
    }

    public function testUpdate(): void
    {
        $user = TestUser::create([
            'email' => 'update@example.com',
            'password' => 'hash',
            'name' => 'Before',
            'role' => 'student',
        ]);

        $rows = TestUser::query()
            ->where('id', $user->id)
            ->update(['name' => 'After']);

        $this->assertEquals(1, $rows);

        $fresh = TestUser::find($user->id);
        $this->assertNotNull($fresh);
        $this->assertEquals('After', $fresh->name);
    }

    public function testDelete(): void
    {
        $user = TestUser::create([
            'email' => 'delete@example.com',
            'password' => 'hash',
            'name' => 'Delete Me',
            'role' => 'student',
        ]);

        $rows = TestUser::query()->where('id', $user->id)->delete();
        $this->assertEquals(1, $rows);
        $this->assertNull(TestUser::find($user->id));
    }

    public function testInsert(): void
    {
        $classId = SchoolClass::create([
            'name' => 'Query Builder Class',
            'join_code' => 'QBTEST01',
        ])->id();

        $studentId = TestUser::create([
            'email' => 'junction@example.com',
            'password' => 'hash',
            'name' => 'Junction',
            'role' => 'student',
        ])->id();

        ClassStudent::query()->insert([
            'class_id' => $classId,
            'user_id' => $studentId,
        ]);

        $this->assertTrue(
            ClassStudent::query()
                ->where('class_id', $classId)
                ->where('user_id', $studentId)
                ->exists()
        );
    }

    public function testCollectionPluck(): void
    {
        $first = TestUser::create([
            'email' => 'pluck1@example.com',
            'password' => 'hash',
            'name' => 'P1',
            'role' => 'student',
        ]);
        $second = TestUser::create([
            'email' => 'pluck2@example.com',
            'password' => 'hash',
            'name' => 'P2',
            'role' => 'student',
        ]);

        $ids = TestUser::query()->whereIn('id', [$first->id, $second->id])->get()->pluck('id');
        sort($ids);

        $this->assertEquals([$first->id, $second->id], $ids);
    }

    public function testUnsupportedOperatorIsRejected(): void
    {
        $threw = false;

        try {
            TestUser::query()->where('email', '; DROP TABLE users; --', 'x')->count();
        } catch (InvalidArgumentException $e) {
            $threw = str_contains($e->getMessage(), 'Unsupported where operator');
        }

        $this->assertTrue($threw);
    }
}
