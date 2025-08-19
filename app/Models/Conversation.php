<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Conversation
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $message
 * @property string $repository
 * @property string $project_directory
 * @property string|null $claude_session_id
 * @property string|null $filename
 * @property bool $is_processing
 * @property bool $archived
 * @property string $mode
 * @property string|null $git_branch
 * @property int|null $pr_number
 * @property int|null $agent_id
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'repository',
        'project_directory',
        'claude_session_id',
        'filename',
        'is_processing',
        'archived',
        'mode',
        'git_branch',
        'pr_number',
        'agent_id',
    ];

    protected $casts = [
        'is_processing' => 'boolean',
        'archived' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
