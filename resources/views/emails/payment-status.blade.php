<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
</head>

<body>
    <h2>Hello {{ $user->name }},</h2>
    <p>Your payment for the template <strong>{{ $template->name }}</strong> has been
        <strong>{{ ucfirst($status) }}</strong>.
    </p>

    @if($status === 'approved')
    <p>You can now access your purchased template.</p>
    @else
    <p>If you believe this was a mistake, please contact our support team.</p>
    @endif

    <p>Thank you,<br> The Admin Team</p>
</body>

</html>