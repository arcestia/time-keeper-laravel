<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ProgressService;

class ProgressController extends Controller
{
    public function __construct(private ProgressService $progress)
    {
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $p = $this->progress->getOrCreate($user->id);
        return response()->json([
            'level' => (int) $p->level,
            'xp' => (int) $p->xp,
            'next_xp' => (int) $p->next_xp,
            'remaining' => max(0, (int) $p->next_xp - (int) $p->xp),
        ]);
    }

    public function addXp(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $data = $request->validate([
            'username' => ['required','string'],
            'amount' => ['required','integer','min:0'],
        ]);
        $target = \App\Models\User::where('username', $data['username'])->firstOrFail();
        $p = $this->progress->addXp($target->id, (int) $data['amount']);
        return response()->json([
            'status' => 'ok',
            'user' => $target->username,
            'level' => (int) $p->level,
            'xp' => (int) $p->xp,
            'next_xp' => (int) $p->next_xp,
        ]);
    }
}
