<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BFAR Regional Fisheries Laboratory XII staff portal">
    <title>Staff Portal | BFAR RFL XII</title>
    <link rel="shortcut icon" href="{{ asset('assets/images/bfarlogo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --deep: #003b67; --ocean: #0065a8; --aqua: #00a7b5; --ink: #17324a; --gold: #f5c343; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; color: var(--ink); font-family: "Segoe UI", Arial, sans-serif; background: #eff7fa; }
        .login-page { min-height: 100vh; display: grid; grid-template-columns: minmax(0, .94fr) minmax(440px, 1.06fr); }
        .welcome-panel { position: relative; display: flex; align-items: center; overflow: hidden; padding: 3rem clamp(2.5rem, 6vw, 7rem); color: #fff; background: linear-gradient(140deg, rgba(0,42,72,.94), rgba(0,96,147,.75)), url('{{ asset('assets/images/BG_LOGIN.jpg') }}') center/cover; }
        .welcome-panel::before, .welcome-panel::after { content: ""; position: absolute; border-radius: 50%; border: 1px solid rgba(255,255,255,.18); }
        .welcome-panel::before { width: 510px; height: 510px; bottom: -300px; left: -160px; box-shadow: 0 0 0 48px rgba(255,255,255,.045), 0 0 0 96px rgba(255,255,255,.035); }
        .welcome-panel::after { width: 290px; height: 290px; top: -150px; right: -100px; }
        .welcome-content { position: relative; z-index: 1; max-width: 570px; }
        .seal { width: 93px; height: 93px; object-fit: contain; padding: 5px; border-radius: 50%; background: rgba(255,255,255,.92); }
        .agency { margin: 1.35rem 0 .9rem; font-size: .73rem; font-weight: 800; letter-spacing: .16em; color: #8eecef; }
        .welcome-panel h1 { margin: 0; font-size: clamp(2.5rem, 4.1vw, 4.25rem); font-weight: 800; letter-spacing: -.045em; line-height: 1.08; }
        .welcome-panel h1 span { color: #78e3e8; }
        .welcome-panel p { max-width: 500px; margin: 1.4rem 0 0; color: #dcecf4; font-size: 1.05rem; line-height: 1.75; }
        .welcome-points { display: flex; flex-wrap: wrap; gap: .75rem 1.5rem; margin-top: 2rem; font-size: .9rem; font-weight: 600; }
        .welcome-points i { color: var(--gold); margin-right: .35rem; }
        .form-panel { display: flex; align-items: center; justify-content: center; padding: 2rem; background: #fff; }
        .form-wrap { width: min(100%, 430px); padding: 2.25rem; border: 1px solid #d5e3ea; border-radius: 1rem; background: #fff; box-shadow: 0 18px 44px rgba(11, 61, 92, .12); }
        .back-link { color: #527084; font-size: .9rem; font-weight: 600; text-decoration: none; }
        .back-link:hover { color: var(--ocean); }
        .form-brand { display: flex; align-items: center; gap: .75rem; margin: 2.2rem 0 2.7rem; }
        .form-brand img { width: 47px; height: 47px; object-fit: contain; mix-blend-mode: multiply; }
        .form-brand strong { color: var(--deep); font-size: .82rem; line-height: 1.35; }
        .form-brand small { color: #698090; font-size: .66rem; font-weight: 600; }
        .form-title { color: var(--deep); font-size: 2rem; font-weight: 800; letter-spacing: -.035em; }
        .form-subtitle { color: #637b8b; margin-bottom: 2rem; }
        .form-label { margin-bottom: .45rem; color: #345066; font-size: .89rem; font-weight: 700; }
        .input-group-text { padding: 0 .9rem; color: #6d8290; border-color: #d7e3e9; border-right: 0; background: #f8fbfc; }
        .form-control { min-height: 51px; border-color: #d7e3e9; border-left: 0; background: #f8fbfc; box-shadow: none!important; }
        .form-control:focus { border-color: var(--aqua); background: #fff; }
        .input-group:focus-within .input-group-text { border-color: var(--aqua); background: #fff; color: var(--ocean); }
        .password-toggle { border: 1px solid #d7e3e9; border-left: 0; color: #6d8290; background: #f8fbfc; }
        .password-toggle:hover { color: var(--ocean); background: #fff; }
        .btn-signin { min-height: 52px; border: 0; border-radius: .65rem; background: var(--ocean); font-weight: 800; letter-spacing: .01em; box-shadow: 0 8px 17px rgba(0,101,168,.2); }
        .btn-signin:hover { background: var(--deep); }
        .security-note { margin-top: 1.7rem; color: #718795; font-size: .79rem; text-align: center; }
        .security-note i { color: var(--aqua); }
        .alert-floating { position: fixed; top: 1rem; right: 1rem; z-index: 1055; min-width: min(390px, calc(100vw - 2rem)); border: 0; border-radius: .75rem; box-shadow: 0 10px 28px rgba(9,47,72,.18); }
        @media (max-width: 900px) { .login-page { grid-template-columns: 1fr; } .welcome-panel { display: none; } .form-panel { min-height: 100vh; } }
        @media (max-width: 460px) { .form-panel { padding: 1rem; } .form-wrap { padding: 1.5rem; } .form-brand { margin: 1.2rem 0 2.2rem; } .form-title { font-size: 1.8rem; } }
    </style>
</head>
<body>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show alert-floating auto-dismiss" role="alert"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show alert-floating auto-dismiss" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif

    <main class="login-page">
        <section class="welcome-panel" aria-label="BFAR Regional Fisheries Laboratory XII">
            <div class="welcome-content">
                <img class="seal" src="{{ asset('assets/images/bfarlogo.png') }}" alt="BFAR Region XII seal">
                <div class="agency">BUREAU OF FISHERIES AND AQUATIC RESOURCES</div>
                <h1>Laboratory data with a <span>purpose.</span></h1>
                <p>Welcome to the Regional Fisheries Laboratory XII Laboratory Information Management System — supporting safe fisheries, trusted results, and better public service.</p>
                <div class="welcome-points"><span><i class="bi bi-check-circle-fill"></i> Secure access</span><span><i class="bi bi-check-circle-fill"></i> Quality-focused workflow</span></div>
            </div>
        </section>

        <section class="form-panel">
            <div class="form-wrap">
                <a href="{{ url('/') }}" class="back-link"><i class="bi bi-arrow-left me-1"></i> Back to homepage</a>
                <div class="form-brand"><img src="{{ asset('assets/images/bfarlogo.png') }}" alt="BFAR Region XII seal"><div><strong>BFAR REGIONAL FISHERIES<br>LABORATORY XII</strong><br><small>GENERAL SANTOS CITY</small></div></div>
                <h1 class="form-title">Welcome back</h1>
                <p class="form-subtitle">Sign in with your staff account to continue.</p>

                <form action="{{ route('login-user') }}" method="POST">
                    @csrf
                    <div class="mb-3"><label class="form-label" for="username">Username</label><div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span><input class="form-control" id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" placeholder="Enter your username" required autofocus></div></div>
                    <div class="mb-4"><label class="form-label" for="password">Password</label><div class="input-group"><span class="input-group-text"><i class="bi bi-key"></i></span><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required><button class="password-toggle" type="button" id="passwordToggle" aria-label="Show password"><i class="bi bi-eye"></i></button></div></div>
                    <button class="btn btn-primary btn-signin w-100" type="submit">Sign in <i class="bi bi-arrow-right ms-1"></i></button>
                </form>
                <p class="security-note"><i class="bi bi-shield-lock-fill me-1"></i> Authorized BFAR personnel only</p>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('passwordToggle').addEventListener('click', function () {
            const password = document.getElementById('password');
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            this.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
            this.innerHTML = '<i class="bi bi-eye' + (visible ? '' : '-slash') + '"></i>';
        });
        setTimeout(() => document.querySelectorAll('.auto-dismiss').forEach(alert => bootstrap.Alert.getOrCreateInstance(alert).close()), 4500);
    </script>
</body>
</html>
