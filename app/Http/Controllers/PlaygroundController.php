<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// API playground (Pertemuan 11) inside the logged-in web app. The user is already signed in with the
// Breeze session, so the page gets its Sanctum Bearer token from that session instead of asking for the
// password a second time.
class PlaygroundController extends Controller
{
    // Name of the tokens issued here, so they can be told apart from Postman / API logins
    private const TOKEN_NAME = 'playground';

    public function show(): View
    {
        return view('playground', ['themes' => array_keys(config('llm.themes'))]);
    }

    // POST /playground/token (session + CSRF): a fresh Bearer token for the logged-in user.
    // Older playground tokens of this user are revoked, so reloading the page never piles tokens up.
    public function token(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->where('name', self::TOKEN_NAME)->delete();

        $minutes = config('sanctum.expiration');
        $token = $user->createToken(self::TOKEN_NAME, ['*'], $minutes ? now()->addMinutes((int) $minutes) : null);

        return response()->json([
            'status' => 'success',
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->format('Y-m-d H:i:s'),
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }
}
