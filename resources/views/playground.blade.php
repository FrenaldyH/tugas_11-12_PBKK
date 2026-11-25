<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Playground API · PromptLab</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400" rel="stylesheet">
    <style>
        /* Design tokens from notes/DESIGN.md (NVIDIA majority, Huawei for gaps) */
        :root {
            --primary: #76b900; --primary-dark: #5a8d00; --on-primary: #000;
            --ink: #000; --body: #1a1a1a; --mute: #757575;
            --canvas: #fff; --surface-soft: #f7f7f7; --surface-dark: #000; --surface-elevated: #1a1a1a;
            --hairline: #ccc; --hairline-strong: #5e5e5e; --on-dark: #fff; --on-dark-mute: rgba(255,255,255,.7);
            --error: #e52020; --success-deep: #3f8500; --link: #0046a4;
            --overlay: rgba(0,0,0,.6); --input-fill: rgba(0,0,0,.05);
            --r-sm: 2px; --r-md: 4px;
            --font: Inter, Arial, Helvetica, sans-serif;
            --mono: "JetBrains Mono", ui-monospace, Menlo, monospace;
        }
        * { box-sizing: border-box; }
        /* Column layout so the footer sits at the bottom even when the content is short */
        body { margin: 0; background: var(--canvas); color: var(--body); font: 400 16px/1.5 var(--font);
               min-height: 100vh; display: flex; flex-direction: column; }
        [hidden] { display: none !important; }
        :focus-visible { outline: 2px solid var(--primary-dark); outline-offset: 2px; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 24px; }

        /* Primary nav: same bar as the workspace (resources/views/layouts/navigation.blade.php) */
        .nav { background: var(--surface-dark); color: var(--on-dark); height: 56px; }
        .nav .bar { display: flex; align-items: center; justify-content: space-between; height: 100%; gap: 16px; padding: 0 20px; }
        .nav-left { display: flex; align-items: center; gap: 32px; height: 100%; }
        .brand { font-weight: 700; font-size: 17px; display: flex; align-items: center; gap: 8px; color: inherit; text-decoration: none; }
        .brand::before { content: ""; width: 14px; height: 14px; background: var(--primary); }
        .nav-links { display: flex; gap: 24px; height: 100%; }
        .nav-links a { display: inline-flex; align-items: center; border-bottom: 4px solid transparent; color: var(--on-dark-mute);
                       font: 700 14px/1 var(--font); text-decoration: none; padding-top: 4px; }
        .nav-links a:hover { color: var(--on-dark); border-bottom-color: rgba(255,255,255,.4); }
        .nav-links a[aria-current="page"] { color: var(--on-dark); border-bottom-color: var(--primary); }
        .nav-user { display: flex; align-items: center; gap: 12px; font-size: 14px; color: var(--on-dark-mute); min-width: 0; }
        .nav-user span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .nav-user form { margin: 0; }
        .crumb a { color: inherit; }
        .crumb a:hover { color: var(--ink); }
        /* Token strip: the Bearer token issued from the web session */
        .token-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
                     margin-bottom: 24px; padding: 12px 16px; background: var(--surface-soft); border-radius: var(--r-sm); font-size: 14px; }
        .token-bar .actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .token-bar .btn { height: 36px; padding: 0 14px; }
        .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--mute); margin-right: 8px; }
        .dot.ok { background: var(--primary); }
        .dot.bad { background: var(--error); }
        .crumb { background: var(--surface-soft); height: 48px; display: flex; align-items: center;
                 font: 700 14px/1.43 var(--font); text-transform: uppercase; color: var(--body); }
        .crumb .wrap { width: 100%; }
        .crumb .sep { color: var(--mute); margin: 0 8px; }

        /* Buttons (NVIDIA; hover darkens per Huawei rule) */
        .btn { font: 700 14px/1.25 var(--font); height: 44px; padding: 0 24px; border-radius: var(--r-md);
               border: 2px solid transparent; cursor: pointer; }
        .btn-primary { background: var(--primary); color: var(--on-primary); }
        .btn-primary:hover, .btn-primary:active { background: var(--primary-dark); }
        .btn-primary:disabled { background: var(--surface-soft); color: var(--mute); cursor: wait; }
        .btn-outline { background: transparent; color: var(--ink); border-color: var(--primary); }
        .btn-outline:hover { border-color: var(--primary-dark); }
        .btn-dark { background: transparent; color: var(--on-dark); border: 1px solid var(--on-dark); height: 36px; padding: 0 16px; }
        .btn-dark:hover { background: var(--surface-elevated); }

        /* Cards with corner-square (NVIDIA) */
        /* main.wrap outranks .wrap, so the vertical padding is not reset by .wrap's "padding: 0 24px";
           width: 100% stops the auto margins from shrinking it inside the flex column */
        main.wrap { padding-top: 32px; padding-bottom: 64px; flex: 1 0 auto; width: 100%; }
        .card { position: relative; border: 1px solid var(--hairline); border-radius: var(--r-sm); padding: 24px; background: var(--canvas); }
        .card::before { content: ""; position: absolute; top: -1px; left: -1px; width: 12px; height: 12px; background: var(--primary); }
        .card h2 { font: 700 20px/1.25 var(--font); color: var(--ink); margin: 0 0 16px; }
        .grid { display: grid; gap: 24px; grid-template-columns: 2fr 3fr; align-items: start; }
        @media (max-width: 1023px) { .grid { grid-template-columns: 1fr; } }
        .login { max-width: 440px; margin: 0 auto; }
        .stack > * + * { margin-top: 16px; }

        /* Form fields (NVIDIA input, Huawei label/helper/error + filled textarea) */
        label, .label { display: block; font: 700 14px/1.43 var(--font); color: var(--ink); margin-bottom: 4px; }
        .input { width: 100%; height: 44px; padding: 0 16px; font: 400 16px var(--font); color: var(--ink);
                 background: var(--canvas); border: 1px solid var(--hairline-strong); border-radius: var(--r-sm); }
        .input:focus, textarea:focus { outline: none; border: 2px solid var(--primary-dark); }
        textarea { width: 100%; min-height: 140px; resize: vertical; padding: 12px 16px; font: 400 16px/1.5 var(--font);
                   background: var(--input-fill); border: 1px solid transparent; border-radius: var(--r-sm); color: var(--ink); }
        .field-foot { display: flex; justify-content: space-between; gap: 8px; margin-top: 4px; font-size: 12px; }
        .helper { color: var(--mute); }
        .counter { font-family: var(--mono); color: var(--mute); }
        .counter.over, .err { color: var(--error); }
        .invalid { border-color: var(--error) !important; }

        /* Mode tabs (NVIDIA pill-tab) */
        .tabs { display: flex; gap: 8px; }
        .tab { font: 700 14px/1 var(--font); padding: 10px 18px; min-height: 44px; border: 1px solid var(--ink);
               border-radius: var(--r-sm); background: transparent; color: var(--ink); cursor: pointer; }
        .tab[aria-pressed="true"] { background: var(--ink); color: var(--on-dark); }

        /* Response panel, badges, alert (designed from NVIDIA tokens) */
        .panel { background: var(--surface-elevated); color: var(--on-dark); font: 400 14px/1.6 var(--mono);
                 border-radius: var(--r-sm); padding: 24px; min-height: 240px; white-space: pre-wrap; word-break: break-word; }
        .panel.empty, .panel.waiting { color: var(--on-dark-mute); }
        .panel.streaming::after { content: ""; display: inline-block; width: 8px; height: 16px; margin-left: 2px;
                                  vertical-align: text-bottom; background: var(--primary); animation: blink 1s steps(1) infinite; }
        @keyframes blink { 50% { opacity: 0; } }
        @media (prefers-reduced-motion: reduce) { .panel.streaming::after { animation: none; } }
        .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        /* Fallback path (Challenge 3): label + badges joined by arrows */
        .route { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 12px; }
        .route-label { font: 700 12px/1.43 var(--font); text-transform: uppercase; color: var(--mute); }
        .route .arrow { color: var(--mute); }
        .badge { background: var(--surface-soft); color: var(--body); font: 700 12px/1.43 var(--font); text-transform: uppercase;
                 border-radius: var(--r-sm); padding: 4px 10px; display: inline-flex; align-items: center; gap: 6px; }
        .badge .mono { font-family: var(--mono); font-weight: 400; text-transform: none; }
        .badge.success::before, .badge.failed::before { content: ""; width: 8px; height: 8px; background: var(--primary); }
        .badge.failed::before { background: var(--error); }
        .alert { background: var(--surface-soft); border-left: 4px solid var(--error); padding: 12px 16px; font-size: 15px; margin-bottom: 12px; }

        /* History table (NVIDIA) */
        .history { margin-top: 24px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 15px; }
        th { background: var(--surface-soft); font: 700 12px/1.43 var(--font); text-transform: uppercase; text-align: left; color: var(--body); }
        th, td { padding: 8px 12px; border-bottom: 1px solid var(--hairline); vertical-align: top; }
        td.mono { font-family: var(--mono); font-size: 13px; white-space: nowrap; }
        td.prompt { max-width: 420px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* Token dialog (Huawei dialog pattern) */
        dialog { border: 0; border-radius: var(--r-sm); padding: 24px; max-width: 480px; width: calc(100% - 32px); }
        dialog::backdrop { background: var(--overlay); }
        dialog h2 { font: 700 20px/1.25 var(--font); margin: 0 0 8px; color: var(--ink); }
        dialog code { display: block; background: var(--surface-elevated); color: var(--on-dark); font: 400 13px/1.5 var(--mono);
                      padding: 12px; border-radius: var(--r-sm); word-break: break-all; margin: 12px 0; }
        .dialog-foot { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }

        footer { background: var(--surface-dark); color: var(--on-dark-mute); font-size: 12px; padding: 24px 0; }
        @media (max-width: 640px) { .wrap { padding: 0 16px; } .nav .bar { padding: 0 16px; } .nav-user span, .nav-links a:not([aria-current]) { display: none; } }
        /* ---- Visual layer shared with the workspace: soft aurora, glowing nav line, gradient buttons ---- */
        body { background:
            radial-gradient(48rem 22rem at 50% -6%, rgba(118,185,0,.12), transparent 70%),
            radial-gradient(30rem 18rem at 100% 0%, rgba(20,184,166,.08), transparent 70%), #fff; }
        .nav { position: relative; }
        .nav::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 1px; opacity: .7;
                      background: linear-gradient(90deg, transparent, rgba(118,185,0,.9) 30%, rgba(20,184,166,.7) 70%, transparent); }
        .brand::before { border-radius: 3px; background: linear-gradient(135deg, #bef264, #76b900 60%, #3f8500);
                         box-shadow: 0 0 12px rgba(118,185,0,.75); }
        .crumb { background: transparent; border-bottom: 1px solid rgba(0,0,0,.06); }
        .btn { border-radius: 10px; }
        .btn-primary { background: linear-gradient(135deg, #a3e635 0%, #76b900 45%, #5a8d00 100%);
                       box-shadow: 0 8px 20px -10px rgba(118,185,0,.9), inset 0 1px 0 rgba(255,255,255,.35); }
        .btn-primary:hover, .btn-primary:active { background: linear-gradient(135deg, #a3e635 0%, #76b900 45%, #5a8d00 100%); filter: brightness(1.05) saturate(1.1); }
        .btn-primary:disabled { background: var(--surface-soft); box-shadow: none; filter: none; }
        .btn-outline { border: 1px solid rgba(0,0,0,.15); background: rgba(255,255,255,.8); }
        .btn-outline:hover { border-color: transparent;
                             background: linear-gradient(#fff,#fff) padding-box, linear-gradient(135deg,#76b900,#14b8a6) border-box; }
        .btn-dark { border-radius: 10px; border-color: rgba(255,255,255,.3); }
        .card { border-radius: 16px; border-color: rgba(0,0,0,.08); background: rgba(255,255,255,.88);
                box-shadow: 0 18px 40px -28px rgba(0,0,0,.45); }
        .card::before { display: none; }
        .card h2 { letter-spacing: -.01em; }
        .tab { border-radius: 10px; border-color: rgba(0,0,0,.15); }
        .tab[aria-pressed="true"] { background: linear-gradient(180deg, #2a2a2a, #000); border-color: transparent; }
        .input { border-radius: 10px; border-color: rgba(0,0,0,.15); }
        textarea { border-radius: 12px; }
        .panel { border-radius: 12px; background: linear-gradient(180deg, #1b1b1b, #0e0e0e); }
        .token-bar { border-radius: 14px; background: linear-gradient(135deg, rgba(163,230,53,.12), rgba(20,184,166,.08));
                     border: 1px solid rgba(63,133,0,.15); }
        .dot.ok { box-shadow: 0 0 10px rgba(118,185,0,.8); }
        th { background: #f7f8f4; }
        dialog { border-radius: 16px; }
        footer { background: linear-gradient(180deg, #0b0b0b, #000); }

        /* ---- Controls, tidied up to match the workspace ---- */
        label, .label { font-weight: 600; }
        /* Option groups as one segmented control (like the Kode / Scan tabs in the workspace) */
        .tabs { display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 4px; max-width: 100%;
                background: linear-gradient(180deg, #f4f4f2, #ecece8); border-radius: 12px;
                box-shadow: inset 0 0 0 1px rgba(0,0,0,.05); }
        .tab { min-height: 36px; height: 36px; padding: 0 16px; border: 0; border-radius: 9px; background: transparent;
               color: #5e5e5e; font: 600 13px/1 var(--font); transition: color .15s, background .15s, box-shadow .15s; }
        .tab { white-space: nowrap; }
        .btn { white-space: nowrap; }
        .tab:hover { color: var(--ink); }
        .tab[aria-pressed="true"] { background: #fff; color: var(--ink);
                                    box-shadow: 0 1px 2px rgba(0,0,0,.1), 0 0 0 1px rgba(0,0,0,.04); }
        /* The fallback test is a "danger" switch: show it in red when on */
        [data-group="simulate"] .tab[data-value="true"][aria-pressed="true"] { color: var(--error); }
        .helper { line-height: 1.5; }
        .field-foot { margin-top: 6px; }

        /* Inputs: one style for select and textarea */
        .input, textarea { background: #fff; border: 1px solid rgba(0,0,0,.14); border-radius: 12px;
                           box-shadow: inset 0 1px 2px rgba(0,0,0,.04); transition: border-color .15s, box-shadow .15s; }
        .input:focus, textarea:focus { border: 1px solid var(--primary-dark); box-shadow: 0 0 0 3px rgba(118,185,0,.18); }
        textarea { min-height: 120px; }

        /* Send: full-width primary button at the bottom of the form */
        #sendBtn { width: 100%; height: 46px; border-radius: 12px; font: 600 15px/1 var(--font); border: 0; }

        /* Secondary buttons: compact and equal */
        .btn-outline { height: 36px; padding: 0 14px; border-radius: 10px; font: 600 13px/1 var(--font); color: var(--ink); }
        .token-bar { align-items: center; }
        .token-bar .actions { gap: 8px; }
        .btn-dark { height: 34px; padding: 0 14px; font: 600 13px/1 var(--font); border: 1px solid rgba(255,255,255,.25); }
        .btn-dark:hover { background: rgba(255,255,255,.1); }

        /* Breadcrumb: quiet, sentence case */
        .crumb { height: 44px; font: 500 13px/1 var(--font); text-transform: none; color: var(--ink); }
        .crumb a { color: var(--mute); text-decoration: none; }
        .crumb a:hover { color: var(--ink); }

        @media (max-width: 640px) {
            .token-bar .actions { width: 100%; }
            .token-bar .btn { flex: 1; }
            .tabs { display: flex; }
            .tab { flex: 1; padding: 0 8px; font-size: 12px; }
        }
    </style>
</head>
<body>
<nav class="nav">
    <div class="bar">
        <div class="nav-left">
            <a class="brand" href="{{ route('workspace') }}">PromptLab</a>
            <div class="nav-links">
                <a href="{{ route('workspace') }}">Workspace</a>
                <a href="{{ route('playground') }}" aria-current="page">Playground API</a>
            </div>
        </div>
        <div class="nav-user">
            <span>{{ auth()->user()->name }}</span>
            {{-- Same session logout as the workspace (Breeze) --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-dark" type="submit">Keluar</button>
            </form>
        </div>
    </div>
</nav>
<div class="crumb"><div class="wrap"><a href="{{ route('workspace') }}">&larr; Workspace</a><span class="sep">/</span>Playground API</div></div>

<main class="wrap">
    {{-- Bearer token for the API calls below, issued from the logged-in session (PlaygroundController@token) --}}
    <div class="token-bar" role="status">
        <div><span class="dot" id="tokenDot"></span><span id="tokenStatus">Menyiapkan token API…</span></div>
        <div class="actions">
            <button class="btn btn-outline" id="showTokenBtn" type="button" disabled title="Untuk dipakai di Postman">Lihat token</button>
            <button class="btn btn-outline" id="newTokenBtn" type="button">Buat token baru</button>
        </div>
    </div>

    {{-- Chat: prompt form (left) and AI answer (right) --}}
    <section id="chatView">
        <div class="grid">
            <form class="card stack" id="chatForm" novalidate>
                <h2>Kirim prompt</h2>
                <div>
                    <span class="label" id="modeLabel">Mode respons</span>
                    <div class="tabs" role="group" aria-labelledby="modeLabel" data-group="mode">
                        <button type="button" class="tab" data-value="normal" aria-pressed="false">Biasa</button>
                        <button type="button" class="tab" data-value="stream" aria-pressed="true">Streaming</button>
                    </div>
                </div>
                <div>
                    {{-- Reasoning mode; default comes from LLM_THINK --}}
                    <span class="label" id="thinkLabel">Mode berpikir</span>
                    <div class="tabs" role="group" aria-labelledby="thinkLabel" aria-describedby="thinkHelp" data-group="think">
                        <button type="button" class="tab" data-value="false" aria-pressed="{{ config('llm.think') ? 'false' : 'true' }}">Mati</button>
                        <button type="button" class="tab" data-value="true" aria-pressed="{{ config('llm.think') ? 'true' : 'false' }}">Aktif</button>
                    </div>
                    <div class="field-foot"><span class="helper" id="thinkHelp">Aktif: model menalar dulu. Bisa lebih matang untuk tugas berat, tetapi jauh lebih lambat dan boros token. Hanya berpengaruh pada model yang mendukung (qwen3 di Senopati); model Ollama lokal mengabaikannya.</span></div>
                </div>
                @if (config('app.debug'))
                    {{-- Challenge 3 test switch; the API also ignores it unless APP_DEBUG=true --}}
                    <div>
                        <span class="label" id="simLabel">Uji fallback (Challenge 3)</span>
                        <div class="tabs" role="group" aria-labelledby="simLabel" aria-describedby="simHelp" data-group="simulate">
                            <button type="button" class="tab" data-value="false" aria-pressed="true">Normal</button>
                            <button type="button" class="tab" data-value="true" aria-pressed="false">Simulasikan {{ ucfirst(config('llm.order')[0] ?? 'provider utama') }} mati</button>
                        </div>
                        <div class="field-foot"><span class="helper" id="simHelp">Urutan provider: {{ implode(' → ', config('llm.order')) }}. Hanya muncul saat APP_DEBUG=true.</span></div>
                    </div>
                @endif
                <div>
                    <label for="theme">Tema agent (system prompt)</label>
                    <select class="input" id="theme">
                        @foreach ($themes as $theme)
                            <option value="{{ $theme }}">{{ $theme }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="prompt">Prompt</label>
                    <textarea id="prompt" maxlength="1200" placeholder="Contoh: Jelaskan middleware di Laravel!"></textarea>
                    <div class="field-foot">
                        <span class="err" id="promptError" role="alert"></span>
                        <span class="counter" id="counter">0/1000</span>
                    </div>
                </div>
                <button class="btn btn-primary" id="sendBtn" type="submit">Kirim</button>
            </form>

            <div class="card">
                <h2>Jawaban AI</h2>
                <div class="alert" id="chatError" role="alert" hidden></div>
                <div class="panel empty" id="answer" aria-live="polite">Jawaban AI akan muncul di sini.</div>
                <div class="route" id="route" hidden></div>
                <div class="meta" id="meta"></div>
            </div>
        </div>

        <div class="card history">
            <h2>Riwayat chat</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Waktu</th><th>Tema</th><th>Provider</th><th>Model</th><th>Token</th><th>Think</th><th>Status</th><th>Prompt</th></tr></thead>
                    <tbody id="historyBody"></tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<footer><div class="wrap">Secure LLM Chat API Hub · Tugas 11 PBKK · Kelompok 1 · Login API dengan email + password tetap tersedia di <code>POST /api/login</code> untuk Postman</div></footer>

{{-- Shows the plain-text token once, like Sanctum does --}}
<dialog id="tokenDialog">
    <h2>Token Bearer Anda</h2>
    <p class="helper">Token ini milik akun yang sedang login dan berlaku sampai <b id="tokenExpiry">-</b>. Salin untuk dipakai di Postman: <b>Authorization: Bearer &lt;token&gt;</b>. "Buat token baru" mencabut token ini.</p>
    <code id="tokenText"></code>
    <div class="dialog-foot">
        <button class="btn btn-outline" id="copyBtn" type="button">Salin</button>
        <button class="btn btn-primary" id="closeDialog" type="button">Tutup</button>
    </div>
</dialog>

<script>
    // Small helpers
    const $ = (id) => document.getElementById(id);

    // Token kept in memory only for this page; the session cookie is what keeps the user logged in
    let token = null;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function setTokenStatus(state, text) {
        $('tokenDot').className = 'dot' + (state ? ' ' + state : '');
        $('tokenStatus').textContent = text;
        $('showTokenBtn').disabled = state !== 'ok';
    }

    // Ask the web app (session cookie + CSRF) for a Bearer token of the logged-in user
    async function issueToken() {
        setTokenStatus(null, 'Menyiapkan token API…');
        const res = await fetch(@json(route('playground.token')), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            credentials: 'same-origin',
        });
        // Session ended (e.g. logged out in another tab): back to the login page
        if (res.status === 401 || res.status === 419) {
            location.assign(@json(route('login')));
            return false;
        }
        if (!res.ok) {
            setTokenStatus('bad', 'Token gagal dibuat. Muat ulang halaman.');
            return false;
        }
        const json = await res.json();
        token = json.access_token;
        $('tokenText').textContent = token;
        $('tokenExpiry').textContent = json.expires_at ?? 'tidak kedaluwarsa';
        setTokenStatus('ok', `Token Bearer aktif untuk ${json.user.email}` + (json.expires_at ? ` · berlaku sampai ${json.expires_at}` : ''));
        return true;
    }

    // fetch() wrapper for /api/*: adds the Bearer token; on 401 (expired/revoked token) gets a new one once
    async function api(path, options = {}, retried = false) {
        if (!token && !(await issueToken())) return new Response('{}', { status: 401 });
        const headers = { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: 'Bearer ' + token };
        const res = await fetch('/api' + path, { ...options, headers });
        if (res.status === 401 && !retried) {
            token = null;
            return api(path, options, true);
        }
        return res;
    }

    async function boot() {
        if (await issueToken()) loadHistory();
    }

    $('showTokenBtn').addEventListener('click', () => $('tokenDialog').showModal());
    $('newTokenBtn').addEventListener('click', async () => {
        if (await issueToken()) $('tokenDialog').showModal();
    });

    $('copyBtn').addEventListener('click', () => navigator.clipboard?.writeText($('tokenText').textContent));
    $('closeDialog').addEventListener('click', () => $('tokenDialog').close());

    // Pill-tab groups (mode, think): exactly one pressed tab per group
    document.querySelectorAll('.tabs').forEach((group) => group.addEventListener('click', (e) => {
        const tab = e.target.closest('.tab');
        if (!tab) return;
        group.querySelectorAll('.tab').forEach((t) => t.setAttribute('aria-pressed', String(t === tab)));
    }));

    // Value of the pressed tab in a group
    const pressed = (group) => document.querySelector(`[data-group="${group}"] [aria-pressed="true"]`)?.dataset.value;

    // Waiting indicator with a live timer ("Menghubungi AI… N s", then "Model sedang berpikir… N s").
    // Cleared when the first answer word arrives; the model's reasoning text itself is never shown.
    let waitTimer = null;
    function startWaiting(answer, label) {
        stopWaiting(answer);
        const t0 = Date.now();
        answer.classList.add('waiting');
        const tick = () => { answer.textContent = `${label}… ${Math.floor((Date.now() - t0) / 1000)} s`; };
        tick();
        waitTimer = setInterval(tick, 1000);
    }
    function stopWaiting(answer) {
        if (!waitTimer) return;
        clearInterval(waitTimer);
        waitTimer = null;
        answer.classList.remove('waiting');
        answer.textContent = '';
    }

    // Live character counter, mirrors the server's max:1000 rule
    $('prompt').addEventListener('input', () => {
        const n = $('prompt').value.length;
        $('counter').textContent = n + '/1000';
        $('counter').classList.toggle('over', n > 1000);
    });

    // Render provider/model/token/status badges under the answer
    function renderMeta(data) {
        $('meta').replaceChildren(...[
            ['Provider', data.provider], ['Model', data.model_used], ['Token', data.tokens?.total ?? '-'],
            ['Think', data.think ? 'aktif' : 'mati'],
        ].map(([k, v]) => {
            const b = document.createElement('span');
            b.className = 'badge';
            b.append(k + ' ');
            const m = document.createElement('span');
            m.className = 'mono';
            m.textContent = v ?? '-';
            b.append(m);
            return b;
        }), statusBadge(data.status));
    }

    function statusBadge(status) {
        const b = document.createElement('span');
        b.className = 'badge ' + status;
        b.textContent = status;
        return b;
    }

    // Fallback path: "Jalur provider  SENOPATI GAGAL: HTTP 401 → OLLAMA BERHASIL"
    function renderRoute(attempts) {
        const box = $('route');
        box.replaceChildren();
        (attempts || []).forEach((a, i) => {
            if (i === 0) {
                const label = document.createElement('span');
                label.className = 'route-label';
                label.textContent = 'Jalur provider';
                box.append(label);
            } else {
                const arrow = document.createElement('span');
                arrow.className = 'arrow';
                arrow.setAttribute('aria-hidden', 'true');
                arrow.textContent = '→';
                box.append(arrow);
            }
            const b = document.createElement('span');
            b.className = 'badge ' + (a.ok ? 'success' : 'failed');
            b.textContent = a.provider + (a.ok ? ' berhasil' : ' gagal: ' + a.reason);
            box.append(b);
        });
        box.hidden = !box.childElementCount;
    }

    // Show validation (422) or server errors
    function showError(res, json) {
        if (res.status === 422 && json.errors?.prompt) {
            $('promptError').textContent = json.errors.prompt[0];
            $('prompt').classList.add('invalid');
        } else if (res.status === 401) {
            setTokenStatus('bad', 'Token tidak berlaku. Klik "Buat token baru".');
        } else {
            $('chatError').textContent = json.message || 'Terjadi kesalahan.';
            $('chatError').hidden = false;
        }
    }

    $('chatForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const answer = $('answer');
        $('promptError').textContent = '';
        $('prompt').classList.remove('invalid');
        $('chatError').hidden = true;
        $('meta').replaceChildren();
        renderRoute([]);
        answer.textContent = '';
        answer.classList.remove('empty');
        $('sendBtn').disabled = true;

        const think = pressed('think') === 'true';
        const body = JSON.stringify({
            prompt: $('prompt').value, theme: $('theme').value, think,
            simulate_primary_down: pressed('simulate') === 'true',
        });
        try {
            if (pressed('mode') === 'normal') {
                answer.textContent = think
                    ? 'Menunggu jawaban lengkap (mode berpikir aktif, bisa jauh lebih lama)...'
                    : 'Menunggu jawaban lengkap...';
                const res = await api('/chat-ai', { method: 'POST', body });
                const json = await res.json();
                answer.textContent = '';
                renderRoute(json.attempts);
                if (!res.ok) return showError(res, json);
                answer.textContent = json.data.ai_response;
                renderMeta(json.data);
            } else {
                await streamAnswer(body, answer);
            }
        } catch (err) {
            $('chatError').textContent = 'Tidak dapat terhubung ke server.';
            $('chatError').hidden = false;
        } finally {
            stopWaiting(answer);
            answer.classList.remove('streaming');
            $('sendBtn').disabled = false;
            loadHistory();
        }
    });

    // Read the SSE stream with fetch (EventSource cannot POST or send headers)
    async function streamAnswer(body, answer) {
        // Headers only arrive with the first event, so show progress right away
        startWaiting(answer, 'Menghubungi AI');
        const res = await api('/chat-ai/stream', { method: 'POST', body });
        if (!res.ok) return showError(res, await res.json());

        answer.classList.add('streaming');
        const attempts = [];
        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        for (;;) {
            const { value, done } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            // Events are separated by a blank line
            let cut;
            while ((cut = buffer.indexOf('\n\n')) !== -1) {
                const raw = buffer.slice(0, cut);
                buffer = buffer.slice(cut + 2);
                const event = raw.match(/^event: (.*)$/m)?.[1];
                const data = JSON.parse(raw.match(/^data: (.*)$/m)?.[1] || '{}');
                if (event === 'attempt') {
                    attempts.push(data);
                    renderRoute(attempts);
                    startWaiting(answer, data.ok
                        ? `Menunggu jawaban dari ${data.provider}`
                        : `${data.provider} gagal, beralih ke provider berikutnya`);
                }
                if (event === 'thinking') startWaiting(answer, 'Model sedang berpikir');
                if (event === 'token') { stopWaiting(answer); answer.textContent += data.delta; }
                if (event === 'done') renderMeta(data.data);
                if (event === 'error') { stopWaiting(answer); $('chatError').textContent = data.message; $('chatError').hidden = false; }
            }
        }
    }

    // Fill the history table (textContent only: AI output is never parsed as HTML)
    async function loadHistory() {
        const res = await api('/chat-ai/history');
        if (!res.ok) return;
        const rows = (await res.json()).data;
        const tbody = $('historyBody');
        if (!rows.length) {
            const tr = tbody.insertRow();
            const td = tr.insertCell();
            td.colSpan = 9;
            td.className = 'helper';
            td.textContent = 'Belum ada riwayat.';
            return tbody.replaceChildren(tr);
        }
        tbody.replaceChildren(...rows.map((r) => {
            const tr = document.createElement('tr');
            [[r.session_id, 'mono'], [r.timestamp, 'mono'], [r.theme, 'mono'], [r.provider ?? '-', 'mono'],
             [r.model_used ?? '-', 'mono'],
             [r.tokens.total ?? '-', 'mono'], [r.think ? 'aktif' : 'mati', 'mono'], [null, ''],
             [r.prompt, 'prompt']].forEach(([text, cls]) => {
                const td = tr.insertCell();
                td.className = cls;
                if (text === null) td.append(statusBadge(r.status)); else td.textContent = text;
                if (cls === 'prompt') td.title = r.prompt;
            });
            return tr;
        }));
    }

    boot();
</script>
</body>
</html>
