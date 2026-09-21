<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'templates' => MessageTemplate::query()
                ->orderBy('name')
                ->get(['id', 'name', 'body']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:4096'],
        ]);

        $template = MessageTemplate::create($validated);

        return response()->json([
            'template' => $template->only(['id', 'name', 'body']),
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MessageTemplate $messageTemplate): JsonResponse
    {
        $messageTemplate->delete();

        return response()->json(['deleted' => true]);
    }
}
