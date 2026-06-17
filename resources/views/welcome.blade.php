<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JCCS Calendar</title>


    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #171717;
            background: #f4f4f2;
        }

        .welcome-page {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
        }

        .login-shell {
            width: min(100%, 1180px);
            height: min(100%, 720px);
            display: grid;
            grid-template-columns: 0.86fr 1.14fr;
            overflow: hidden;
            border-radius: 28px;
            background: #ffffff;
            box-shadow: 0 28px 90px rgba(0, 0, 0, 0.14);
        }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 48px 40px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
        }

        .login-content {
            width: min(100%, 420px);
            transform: translateY(-8px);
        }

        .brand-logo {
            margin-bottom: 14px;
            display: flex;
            justify-content: center;
        }

        .brand-logo img {
            display: block;
            width: 180px;
            height: auto;
            margin: 0 auto;
        }

        .page-title {
            margin: 0;
            font-size: clamp(30px, 3.4vw, 40px);
            line-height: 1.05;
            font-weight: 700;
            color: #111111;
        }

        .description {
            margin: 12px 0 0;
            max-width: 390px;
            font-size: 15px;
            line-height: 1.6;
            color: #5f5f5f;
        }

        .login-instruction {
            margin: 20px 0 24px;
            font-size: 14px;
            font-weight: 600;
            color: #272727;
        }

        .auth-message {
            margin-bottom: 16px;
            padding: 10px 12px;
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.45;
            font-weight: 700;
        }

        .auth-message-error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fecaca;
        }

        .auth-message-success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
            color: #222222;
        }

        .form-input {
            width: 100%;
            min-height: 44px;
            padding: 11px 14px;
            border: 1px solid #d7d7d7;
            border-radius: 9px;
            background: #ffffff;
            color: #171717;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        .password-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .forgot-link {
            font-size: 12px;
            font-weight: 700;
            color: #2563eb;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .primary-button,
        .secondary-button {
            width: 100%;
            min-height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9px;
            font-size: 14px;
            font-family: inherit;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: transform 160ms ease, background-color 160ms ease, box-shadow 160ms ease;
        }

        .primary-button {
            margin-top: 12px;
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.18);
        }

        .primary-button:hover,
        .secondary-button:hover {
            transform: translateY(-1px);
        }

        .primary-button:hover {
            background: #1d4ed8;
            box-shadow: 0 14px 26px rgba(37, 99, 235, 0.22);
        }

        .divider-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 28px 0;
            color: #8b8b8b;
            font-size: 12px;
            font-weight: 700;
        }

        .divider-row::before,
        .divider-row::after {
            content: "";
            height: 1px;
            flex: 1;
            background: #e4e4e4;
        }

        .secondary-button {
            background: #f4f4f2;
            color: #171717;
            border: 1px solid #d9d9d7;
        }

        .secondary-button:hover {
            background: #e9e9e6;
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.08);
        }

        .visual-panel {
            position: relative;
            overflow: hidden;
            height: 100%;
            min-height: 0;
            border-radius: 0;
            background: #f8fafc;
        }

        .welcome-image {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
                height: auto;
                max-height: calc(100vh - 36px);
            }

            .visual-panel {
                display: none;
            }

            .login-panel {
                padding: 30px 28px 34px;
            }
            .login-content {
                transform: translateY(-4px);
            }
        }

        @media (max-width: 520px) {
            .welcome-page {
                padding: 14px;
            }

            .login-panel {
                padding: 28px 22px 28px;
            }
            .login-content {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="welcome-page">
        <main class="login-shell">
            <section class="login-panel">
                <div class="login-content">
                    <div class="brand-logo">
                        <img src="{{ asset('images/jccs-logo.png') }}" alt="JCCS Services Logo">
                    </div>

                    <h1 class="page-title">JCCS Calendar</h1>

                    <p class="description">
                        Manage events, assigned work, project dates, team schedules, and job status from one organized internal calendar.
                    </p>

                    <p class="login-instruction">Enter your credentials to log in.</p>

                    @if (session('status'))
                        <div class="auth-message auth-message-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="auth-message auth-message-error">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="primary-button">
                                Open Dashboard
                            </a>
                        @else
                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <div class="form-group">
                                    <label for="email" class="form-label">Email address</label>
                                    <input
                                        id="email"
                                        class="form-input"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        placeholder="Enter your email"
                                    >
                                </div>

                                <div class="form-group">
                                    <div class="password-row">
                                        <label for="password" class="form-label">Password</label>

                                        @if (Route::has('password.request'))
                                            <a href="{{ route('password.request') }}" class="forgot-link">
                                                Forgot password?
                                            </a>
                                        @endif
                                    </div>

                                    <input
                                        id="password"
                                        class="form-input"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="Enter your password"
                                    >
                                </div>

                                <input type="hidden" name="remember" value="1">

                                <button type="submit" class="primary-button">
                                    Login
                                </button>
                            </form>

                            @if (Route::has('register'))
                                <div class="divider-row">or</div>

                                <a href="{{ route('register') }}" class="secondary-button">
                                    Register Now
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </section>

            <section class="visual-panel" aria-hidden="true">
                <img src="{{ asset('images/welcome_image.png') }}" alt="" class="welcome-image">
            </section>
        </main>
    </div>
</body>
</html>