<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Whaspil POS – Login</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-body">

<div class="login-container">
    <div class="login-logo">
        <img src="/images/whaspil_logo.png" alt="Whaspil Restaurant" class="login-logo-img">
        <h1 class="login-brand">Whaspil Restaurant</h1>
        <p class="login-sub">Point of Sale System</p>
    </div>

    @if($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-input" placeholder="Enter username"
                   autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')">
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-eye">
                <input type="password" name="password" id="loginPassword" class="form-input"
                       placeholder="Enter password"
                       autocomplete="new-password" readonly
                       onfocus="this.removeAttribute('readonly')">
                <button type="button" class="eye-btn" onclick="togglePassword('loginPassword', this)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-primary" style="margin-top:8px;">Login</button>
    </form>
</div>

<style>
.input-eye {
    position: relative;
    display: flex;
    align-items: center;
}
.input-eye .form-input { padding-right: 44px; }
.eye-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--muted);
    padding: 4px;
    display: flex;
    align-items: center;
}
</style>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const eyeOpen   = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = eyeClosed;
        } else {
            input.type = 'password';
            btn.innerHTML = eyeOpen;
        }
    }
</script>
</body>
</html>
