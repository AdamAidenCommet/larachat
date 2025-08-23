<?php

namespace App\Models;

use App\Services\AgentSyncService;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'prompt',
        'tools',
    ];

    protected static function booted()
    {
        static::created(function (Agent $agent) {
            app(AgentSyncService::class)->syncAgent($agent);
        });

        static::updated(function (Agent $agent) {
            app(AgentSyncService::class)->syncAgent($agent);
        });

        static::deleted(function (Agent $agent) {
            app(AgentSyncService::class)->removeAgent($agent);
        });
    }
}
