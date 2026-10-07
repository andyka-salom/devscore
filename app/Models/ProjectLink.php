<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'url', 'sort_order'])]
class ProjectLink extends Model
{
    public $timestamps = false;
}
