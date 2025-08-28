<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Agent
 *
 * @property int $id
 * @property string $name
 * @property string $prompt
 */
class Agent extends Model
{
    protected $fillable = [
        'name',
        'prompt',
    ];
}
