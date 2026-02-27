<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OmniPost Setup</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; }
        .card { max-width: 520px; margin: 8vh auto; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,.08); }
        label { display: block; font-weight: 600; margin: 12px 0 6px; }
        input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
        button { margin-top: 16px; width: 100%; background: #111827; color: #fff; border: 0; padding: 12px; border-radius: 8px; cursor: pointer; }
        .error { color: #b91c1c; margin-top: 8px; font-size: 14px; }
        .hint { color: #4b5563; font-size: 14px; margin-top: 8px; }
    </style>
</head>
<body>
<div class="card">
    <h2>Initial Setup</h2>
    <p class="hint">Create the first administrator account. This screen appears only when no users exist.</p>

    <form method="POST" action="{{ route('setup.store') }}">
        @csrf

        <label>Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required>

        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Confirm Password</label>
        <input type="password" name="password_confirmation" required>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <button type="submit">Create Admin Account</button>
    </form>
</div>
</body>
</html>
