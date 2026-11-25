<?php

namespace App\Http\Controllers;

use App\Livewire\ChatBox;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Agentic AI workspace page. The Livewire components do the work; this only picks the conversation
// to show first, so a page refresh reopens where the user left off.
class WorkspaceController extends Controller
{
    public function __invoke(Request $request): View
    {
        // Coming from "ask the AI" on the scan page: start a new conversation with the prepared prompt
        $conversationId = $request->session()->has(ChatBox::PREFILL)
            ? null
            : $request->user()->conversations()->latest('updated_at')->latest('id')->value('id');

        return view('workspace', ['conversationId' => $conversationId]);
    }
}
