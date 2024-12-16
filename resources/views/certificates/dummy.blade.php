<!DOCTYPE html>
<html>

<head>
    <title>Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .certificate-container {
            text-align: center;
            padding: 50px;
        }

        .certificate-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .certificate-details {
            font-size: 18px;
        }

        .certificate-name {
            font-size: 24px;
            font-weight: bold;
        }

        .certificate-course {
            font-size: 20px;
            font-style: italic;
        }
    </style>
</head>

<body>
    {{-- @dd($user, $course) --}}
    <div class="certificate-container">
        <div class="certificate-title">Certificate of Completion </div>
        <div class="certificate-details">
            This is to certify that
            <div class="certificate-name">{{ $user->first_name }} {{ $user->last_name }}</div>
            has completed the course
            <div class="certificate-course">{{ $course->title }}</div>
        </div>
    </div>
</body>

</html>
