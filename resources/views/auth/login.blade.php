<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk — HALOHA POS</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth">
<div class="card">
<img class="brand-logo" src="{{ asset('images/haloha-logo.jpeg') }}" alt="Haloha — Halal, Original, Happiness" width="447" height="447">
<h3>Masuk ke POS</h3>@if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif<form class="form" method="post" action="{{ route('login.store') }}">@csrf<label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
</label>
<label>Password<input type="password" name="password" required autocomplete="current-password">
</label>
<label style="display:flex;align-items:center">
<input style="width:auto" type="checkbox" name="remember" value="1"> Ingat saya</label>
<button class="primary" style="width:100%">Masuk</button>
</form>
</div>
</div>
</body>
</html>
