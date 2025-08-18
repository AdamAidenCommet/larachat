<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $agents = Agent::orderBy('created_at', 'desc')->get();
        
        // If it's an API request, return JSON
        if ($request->is('api/*')) {
            return response()->json(['agents' => $agents]);
        }
        
        return Inertia::render('Agents/Index', [
            'agents' => $agents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prompt' => 'required|string',
        ]);

        $agent = Agent::create($validated);

        // If it's an API request, return JSON
        if ($request->is('api/*')) {
            return response()->json(['agent' => $agent, 'message' => 'Agent created successfully']);
        }

        return redirect()->route('agents.index');
    }

    public function update(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prompt' => 'required|string',
        ]);

        $agent->update($validated);

        return redirect()->route('agents.index');
    }

    public function destroy(Agent $agent)
    {
        $agent->delete();

        return redirect()->route('agents.index');
    }
}
