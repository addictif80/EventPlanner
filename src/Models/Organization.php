<?php

namespace App\Models;

use App\Core\Model;

class Organization extends Model
{
    protected static string $table = 'organizations';
    protected static bool $scoped = false;
}
