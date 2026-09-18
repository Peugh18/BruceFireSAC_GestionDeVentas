<?php

use App\Http\Controllers\AiAssistantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:ai_assistant.view'])->group(function () {
    Route::get('ai-assistant', [AiAssistantController::class, 'index'])->name('ai-assistant.index');
    Route::post('ai-assistant/ask', [AiAssistantController::class, 'ask'])->name('ai-assistant.ask');
});
