<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskAssistantRequest;
use App\Services\AI\GeminiAssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiAssistantController extends Controller
{
    public function __construct(private readonly GeminiAssistantService $assistantService) {}

    /**
     * Display the asistente gerencial panel.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('ai-assistant/index', [
            'question' => $request->session()->get('question'),
            'answer' => $request->session()->get('answer'),
        ]);
    }

    /**
     * Ask the assistant a question about data already in the system.
     */
    public function ask(AskAssistantRequest $request): RedirectResponse
    {
        $question = $request->string('question')->toString();
        $answer = $this->assistantService->ask($request->user(), $question);

        return back()->with(['question' => $question, 'answer' => $answer]);
    }
}
