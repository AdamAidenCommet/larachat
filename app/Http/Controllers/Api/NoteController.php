<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $notes = auth()->user()->notes()->latest()->get();
        return response()->json($notes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $note = auth()->user()->notes()->create($validated);

        return response()->json($note, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Note $note): JsonResponse
    {
        $this->authorize('view', $note);
        return response()->json($note);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $note->update($validated);

        return response()->json($note);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note): JsonResponse
    {
        $this->authorize('delete', $note);
        $note->delete();

        return response()->json(null, 204);
    }
}
