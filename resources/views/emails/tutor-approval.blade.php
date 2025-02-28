<!DOCTYPE html>
<html>

<head>
    <title>Your Tutor Request Has Been Approved</title>
</head>

<body>
    <p>Dear {{ $tutor->user->name }},</p>
    <p>{{ $message }}</p>
    <p>Thank you,</p>
    <p>The Team</p>
</body>

</html>
