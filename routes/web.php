<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\ConversationsController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\RepositoryDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing');
})->name('home');

// API Documentation
Route::get('/docs', [DocsController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('docs');

// Redirect from /api/docs to /docs for convenience
Route::get('/api/docs', function () {
    return redirect('/docs');
});

Route::get('/dashboard', function () {
    return redirect('/claude');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('repository', [RepositoryDashboardController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('repository.dashboard');

// Agents routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
    Route::post('agents', [AgentController::class, 'store'])->name('agents.store');
    Route::put('agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::delete('agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');
});

Route::get('claude', function () {
    return Inertia::render('Claude', [
        'repository' => request()->query('repository'),
    ]);
})->middleware(['auth', 'verified'])->name('claude');

Route::get('claude/new', [ConversationsController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('claude.new');

// //
// //Route::get('claude/{session}', function ($session) {
// //    return Inertia::render('Claude', [
// //        'sessionFile' => $session,
// //        'repository' => request()->query('repository')
// //    ]);
// //})->middleware(['auth', 'verified'])->name('claude.session');
// a

Route::get('claude/conversation/{conversation}', function ($conversation) {
    $conv = \App\Models\Conversation::with('agent')->findOrFail($conversation);
    return Inertia::render('Claude', [
        'conversationId' => $conv->id,
        'conversationTitle' => $conv->title,
        'repository' => $conv->repository,
        'sessionId' => $conv->claude_session_id,
        'sessionFile' => $conv->filename,
        'isArchived' => $conv->archived ?? false,
        'agent' => $conv->agent
    ]);
})->middleware(['auth', 'verified'])->name('claude.conversation');

Route::get('claude/conversation/{conversation}/diff', [ConversationsController::class, 'showDiff'])
    ->middleware(['auth', 'verified'])
    ->name('claude.conversation.diff');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
