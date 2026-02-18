@php
    $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name'));
    $institutionName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_NAME, '');
    $logoUrl = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_LOGO, '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your password</title>
</head>
<body style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f5; padding: 24px;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 28px 24px; text-align: center;">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 48px; max-width: 140px; display: inline-block; vertical-align: middle;" />
            @else
                <span style="font-size: 1.5rem; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">{{ $appName }}</span>
            @endif
        </div>
        <div style="padding: 28px 24px;">
            <h1 style="margin: 0 0 8px 0; font-size: 1.25rem; font-weight: 600; color: #111827;">Reset your password</h1>
            <p style="margin: 0 0 20px 0; font-size: 0.9375rem; line-height: 1.5; color: #4b5563;">Hello {{ $user->name ?: $user->username }},</p>
            <p style="margin: 0 0 20px 0; font-size: 0.9375rem; line-height: 1.5; color: #4b5563;">You requested a password reset for your {{ $appName }} account. Click the button below to set a new password. This link expires in 60 minutes.</p>
            <p style="margin: 0 0 24px 0; text-align: center;">
                <a href="{{ $resetUrl }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: #ffffff !important; text-decoration: none; font-weight: 600; font-size: 0.9375rem; border-radius: 8px;">Reset password</a>
            </p>
            <p style="margin: 0; font-size: 0.8125rem; color: #6b7280;">If you did not request this, you can ignore this email. Your password will not be changed.</p>
        </div>
        <div style="padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; font-size: 0.75rem; color: #6b7280; text-align: center;">This is an automated message. Please do not reply to this email. Replies are not monitored.</p>
            @if($institutionName)
                <p style="margin: 6px 0 0 0; font-size: 0.75rem; color: #9ca3af; text-align: center;">{{ $institutionName }}</p>
            @endif
            <p style="margin: 4px 0 0 0; font-size: 0.75rem; color: #9ca3af; text-align: center;">{{ $appName }} &copy; 2026</p>
        </div>
    </div>
</body>
</html>
