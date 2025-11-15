<?php

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\GuildJoinRequest;
use App\Services\ProgressService;
use App\Services\TimeTokenService;
use App\Services\GuildLevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuildController extends Controller
{
    public function page()
    {
        $user = Auth::user();
        abort_unless($user, 403);
        return view('guilds.index');
    }

    public function me(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $member = GuildMember::with('guild.owner')
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return response()->json(['ok' => true, 'guild' => null, 'me_id' => (int) $user->id]);
        }

        $guild = $member->guild;
        $members = GuildMember::with('user')
            ->where('guild_id', $guild->id)
            ->orderBy('role')
            ->orderBy('id')
            ->get();

        $joinRequests = [];
        if ($member->role === 'leader') {
            $joinRequests = GuildJoinRequest::with('user')
                ->where('guild_id', $guild->id)
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get()
                ->map(function (GuildJoinRequest $r) {
                    return [
                        'id' => $r->id,
                        'user_id' => $r->user_id,
                        'username' => optional($r->user)->username,
                        'created_at' => $r->created_at,
                    ];
                })->all();
        }

        return response()->json([
            'ok' => true,
            'me_id' => (int) $user->id,
            'guild' => [
                'id' => $guild->id,
                'name' => $guild->name,
                'description' => $guild->description,
                'owner_user_id' => $guild->owner_user_id,
                'is_locked' => (bool) $guild->is_locked,
                'is_private' => (bool) $guild->is_private,
                'level' => (int) ($guild->level ?? 1),
                'xp' => (int) ($guild->xp ?? 0),
                'total_xp' => (int) ($guild->total_xp ?? 0),
                'next_xp' => (int) ($guild->next_xp ?? app(GuildLevelService::class)->nextXpForLevel((int) ($guild->level ?? 1))),
                'members' => $members->map(function (GuildMember $gm) {
                    return [
                        'id' => $gm->id,
                        'user_id' => $gm->user_id,
                        'username' => optional($gm->user)->username,
                        'role' => $gm->role,
                        'contribution_xp' => (int) ($gm->contribution_xp ?? 0),
                    ];
                })->all(),
                'join_requests' => $joinRequests,
            ],
        ]);
    }

    public function list(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $rows = Guild::query()
            ->withCount('members')
            ->orderBy('members_count', 'desc')
            ->orderBy('name')
            ->limit(100)
            ->get();

        return response()->json([
            'ok' => true,
            'guilds' => $rows->map(function (Guild $g) {
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'description' => $g->description,
                    'is_locked' => (bool) $g->is_locked,
                    'is_private' => (bool) $g->is_private,
                    'owner_user_id' => (int) $g->owner_user_id,
                    'members' => (int) $g->members_count,
                    'level' => (int) ($g->level ?? 1),
                    'xp' => (int) ($g->xp ?? 0),
                    'total_xp' => (int) ($g->total_xp ?? 0),
                    'next_xp' => (int) ($g->next_xp ?? app(GuildLevelService::class)->nextXpForLevel((int) ($g->level ?? 1))),
                ];
            })->all(),
        ]);
    }

    public function leaderboard(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $rows = Guild::query()
            ->withCount('members')
            ->orderByDesc('total_xp')
            ->orderByDesc('level')
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json([
            'ok' => true,
            'guilds' => $rows->map(function (Guild $g, int $idx) {
                return [
                    'rank' => $idx + 1,
                    'id' => $g->id,
                    'name' => $g->name,
                    'level' => (int) ($g->level ?? 1),
                    'total_xp' => (int) ($g->total_xp ?? 0),
                    'members' => (int) $g->members_count,
                ];
            })->all(),
        ]);
    }

    public function create(Request $request, ProgressService $progress, TimeTokenService $tokens): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $existing = GuildMember::where('user_id', $user->id)->first();
        if ($existing) {
            return response()->json(['ok' => false, 'message' => 'You are already in a guild'], 422);
        }

        $data = $request->validate([
            'name' => ['required','string','max:60'],
            'description' => ['nullable','string','max:255'],
        ]);

        $p = $progress->getOrCreate($user->id);
        if ((int)$p->level < 1000) {
            return response()->json(['ok' => false, 'message' => 'You must be at least level 1000 to create a guild'], 422);
        }

        $bal = $tokens->getBalances($user->id);
        $haveBlack = (int)($bal['black'] ?? 0);
        if ($haveBlack < 1) {
            return response()->json(['ok' => false, 'message' => 'You need at least 1 black time token to create a guild'], 422);
        }

        $result = $tokens->debit($user->id, 'black', 1);
        if (!($result['ok'] ?? false) || (int)($result['taken'] ?? 0) < 1) {
            return response()->json(['ok' => false, 'message' => 'Failed to consume black time token'], 422);
        }

        $name = trim($data['name']);

        $guild = DB::transaction(function () use ($user, $name, $data) {
            if (Guild::where('name', $name)->lockForUpdate()->exists()) {
                abort(422, 'Guild name already taken');
            }
            $guild = Guild::create([
                'name' => $name,
                'description' => $data['description'] ?? null,
                'owner_user_id' => $user->id,
                'is_locked' => false,
            ]);

            GuildMember::create([
                'guild_id' => $guild->id,
                'user_id' => $user->id,
                'role' => 'leader',
            ]);

            return $guild->refresh();
        });

        return response()->json([
            'ok' => true,
            'guild_id' => (int) $guild->id,
        ]);
    }

    public function join(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'guild_id' => ['required','integer','min:1'],
        ]);

        $existing = GuildMember::where('user_id', $user->id)->first();
        if ($existing) {
            return response()->json(['ok' => false, 'message' => 'You are already in a guild'], 422);
        }

        $guild = Guild::find($data['guild_id']);
        if (!$guild) {
            return response()->json(['ok' => false, 'message' => 'Guild not found'], 404);
        }
        if ($guild->is_locked) {
            return response()->json(['ok' => false, 'message' => 'This guild is locked and not accepting changes'], 422);
        }

        if ($guild->is_private) {
            // Create join request instead of joining directly
            $existingReq = GuildJoinRequest::where(['guild_id' => $guild->id, 'user_id' => $user->id])
                ->where('status', 'pending')
                ->first();
            if ($existingReq) {
                return response()->json(['ok' => false, 'message' => 'Join request already pending'], 422);
            }
            GuildJoinRequest::updateOrCreate([
                'guild_id' => $guild->id,
                'user_id' => $user->id,
            ], [
                'status' => 'pending',
            ]);
            return response()->json(['ok' => true, 'requested' => true]);
        }

        return DB::transaction(function () use ($user, $guild) {
            $count = GuildMember::where('guild_id', $guild->id)->lockForUpdate()->count();
            if ($count >= 50) {
                return response()->json(['ok' => false, 'message' => 'Guild is full (max 50 members)'], 422);
            }
            $existing = GuildMember::where('user_id', $user->id)->lockForUpdate()->first();
            if ($existing) {
                return response()->json(['ok' => false, 'message' => 'You are already in a guild'], 422);
            }

            GuildMember::create([
                'guild_id' => $guild->id,
                'user_id' => $user->id,
                'role' => 'member',
            ]);

            return response()->json(['ok' => true]);
        });
    }

    public function donateTokens(Request $request, TimeTokenService $tokens, GuildLevelService $levels): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'guild_id' => ['required','integer','min:1'],
            'color' => ['required','string','in:red,blue,green,yellow,black'],
            'quantity' => ['required','integer','min:1'],
        ]);

        $member = GuildMember::where(['user_id' => $user->id, 'guild_id' => $data['guild_id']])->first();
        if (!$member) {
            return response()->json(['ok' => false, 'message' => 'You are not in that guild'], 422);
        }

        $guild = Guild::findOrFail($data['guild_id']);

        // Debit tokens first
        $res = $tokens->debit($user->id, $data['color'], (int) $data['quantity']);
        if (!(bool)($res['ok'] ?? false) || (int)($res['taken'] ?? 0) <= 0) {
            return response()->json(['ok' => false, 'message' => 'Not enough tokens'], 422);
        }
        $taken = (int) $res['taken'];

        $rates = [
            'red' => 10,
            'blue' => 40,
            'green' => 520,
            'yellow' => 5200,
            'black' => 52000,
        ];
        $color = strtolower($data['color']);
        $xp = $taken * ($rates[$color] ?? 0);
        if ($xp <= 0) {
            return response()->json(['ok' => false, 'message' => 'No XP gained'], 422);
        }

        $updated = $levels->addXp($guild, $xp);
        // Track member contribution XP on the guild member row
        $member->increment('contribution_xp', $xp);

        return response()->json([
            'ok' => true,
            'taken' => $taken,
            'xp_added' => $xp,
            'guild' => [
                'id' => $updated->id,
                'level' => (int) $updated->level,
                'xp' => (int) $updated->xp,
                'total_xp' => (int) $updated->total_xp,
                'next_xp' => (int) $updated->next_xp,
            ],
        ]);
    }

    public function adminGrantXp(Request $request, GuildLevelService $levels): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);

        $data = $request->validate([
            'guild_id' => ['required','integer','min:1'],
            'xp' => ['required','integer','min:1'],
        ]);

        $guild = Guild::findOrFail($data['guild_id']);
        $updated = $levels->addXp($guild, (int) $data['xp']);

        return response()->json([
            'ok' => true,
            'guild' => [
                'id' => $updated->id,
                'level' => (int) $updated->level,
                'xp' => (int) $updated->xp,
                'total_xp' => (int) $updated->total_xp,
                'next_xp' => (int) $updated->next_xp,
            ],
        ]);
    }

    public function leave(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $member = GuildMember::with('guild')->where('user_id', $user->id)->first();
        if (!$member) {
            return response()->json(['ok' => false, 'message' => 'You are not in a guild'], 422);
        }

        $guild = $member->guild;
        if ($guild && $guild->is_locked) {
            return response()->json(['ok' => false, 'message' => 'Guild is locked; members cannot leave'], 422);
        }

        if ($member->role === 'leader') {
            return response()->json(['ok' => false, 'message' => 'Leader must disband or transfer leadership before leaving'], 422);
        }

        $member->delete();

        return response()->json(['ok' => true]);
    }

    public function updateVisibility(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'guild_id' => ['required','integer','min:1'],
            'is_private' => ['required','boolean'],
        ]);

        $member = GuildMember::where('user_id', $user->id)->first();
        if (!$member || $member->role !== 'leader' || $member->guild_id !== $data['guild_id']) {
            return response()->json(['ok' => false, 'message' => 'Only the guild leader can change visibility'], 422);
        }

        $guild = Guild::findOrFail($data['guild_id']);
        $guild->is_private = (bool) $data['is_private'];
        $guild->save();

        return response()->json(['ok' => true, 'is_private' => (bool) $guild->is_private]);
    }

    public function approveRequest(int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        return DB::transaction(function () use ($user, $id) {
            $req = GuildJoinRequest::lockForUpdate()->findOrFail($id);
            if ($req->status !== 'pending') {
                return response()->json(['ok' => false, 'message' => 'Request already handled'], 422);
            }

            $member = GuildMember::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$member || !in_array($member->role, ['leader','officer'], true) || $member->guild_id !== $req->guild_id) {
                return response()->json(['ok' => false, 'message' => 'Only leader or officer can approve'], 422);
            }

            // Ensure target is not already in a guild
            $targetMember = GuildMember::where('user_id', $req->user_id)->lockForUpdate()->first();
            if ($targetMember) {
                $req->status = 'denied';
                $req->save();
                return response()->json(['ok' => false, 'message' => 'User already in a guild'], 422);
            }

            $guild = Guild::lockForUpdate()->findOrFail($req->guild_id);
            if ($guild->is_locked) {
                return response()->json(['ok' => false, 'message' => 'Guild is locked'], 422);
            }

            $count = GuildMember::where('guild_id', $guild->id)->count();
            if ($count >= 50) {
                return response()->json(['ok' => false, 'message' => 'Guild is full'], 422);
            }

            GuildMember::create([
                'guild_id' => $guild->id,
                'user_id' => $req->user_id,
                'role' => 'member',
            ]);

            $req->status = 'approved';
            $req->save();

            return response()->json(['ok' => true]);
        });
    }

    public function denyRequest(int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $req = GuildJoinRequest::findOrFail($id);
        $member = GuildMember::where('user_id', $user->id)->first();
        if (!$member || !in_array($member->role, ['leader','officer'], true) || $member->guild_id !== $req->guild_id) {
            return response()->json(['ok' => false, 'message' => 'Only leader or officer can deny'], 422);
        }
        $req->status = 'denied';
        $req->save();

        return response()->json(['ok' => true]);
    }

    public function updateMemberRole(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'role' => ['required','string','in:member,officer'],
        ]);

        return DB::transaction(function () use ($user, $id, $data) {
            $actor = GuildMember::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$actor || $actor->role !== 'leader') {
                return response()->json(['ok' => false, 'message' => 'Only the guild leader can change roles'], 422);
            }

            $target = GuildMember::lockForUpdate()->findOrFail($id);
            if ($target->guild_id !== $actor->guild_id) {
                return response()->json(['ok' => false, 'message' => 'Cannot change role for another guild'], 422);
            }
            if ($target->role === 'leader') {
                return response()->json(['ok' => false, 'message' => 'Cannot change leader role'], 422);
            }

            $target->role = $data['role'];
            $target->save();

            return response()->json(['ok' => true, 'role' => $target->role]);
        });
    }

    public function disband(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $member = GuildMember::with('guild')->where('user_id', $user->id)->first();
        if (!$member || !$member->guild) {
            return response()->json(['ok' => false, 'message' => 'You are not in a guild'], 422);
        }

        $guild = $member->guild;
        if ($guild->is_locked) {
            return response()->json(['ok' => false, 'message' => 'Guild is locked and cannot be disbanded'], 422);
        }
        if ($member->role !== 'leader') {
            return response()->json(['ok' => false, 'message' => 'Only the guild leader can disband the guild'], 422);
        }

        DB::transaction(function () use ($guild) {
            GuildMember::where('guild_id', $guild->id)->delete();
            $guild->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function adminLock(int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);

        $guild = Guild::findOrFail($id);
        $guild->is_locked = true;
        $guild->save();

        return response()->json(['ok' => true]);
    }

    public function adminUnlock(int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);

        $guild = Guild::findOrFail($id);
        $guild->is_locked = false;
        $guild->save();

        return response()->json(['ok' => true]);
    }
}
