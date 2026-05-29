<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use Framework\Model\Model;

#[Table('class_teacher')]
final class ClassTeacher extends Model
{
    #[PrimaryKey]
    #[Column(name: 'class_id', type: 'integer')]
    public int $classId;

    #[Column(name: 'user_id', type: 'integer')]
    public int $userId;
}
