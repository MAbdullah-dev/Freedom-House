<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate of completion</title>
    <style>
        body {
            margin: 0;
            color: #14253d;
            font-family: DejaVu Sans, sans-serif;
        }
        .sheet {
            border: 8px solid #14253d;
            padding: 36px;
        }
        .mark {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .provisional {
            margin: 4px 0 28px;
            color: #5c6b7a;
            font-size: 11px;
        }
        h1 {
            margin: 0 0 24px;
            color: #087f8c;
            font-size: 22px;
        }
        .label {
            margin: 0;
            color: #5c6b7a;
            font-size: 12px;
        }
        h2 {
            margin: 4px 0 18px;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <p class="mark">Stellix Soft LMS</p>
        <p class="provisional">Provisional wordmark. This is not an official logo.</p>
        <h1>Certificate of completion</h1>
        <p class="label">Presented to</p>
        <h2>{{ trim($user->first_name.' '.$user->last_name) }}</h2>
        <p class="label">For completing</p>
        <h2>{{ $course->title }}</h2>
        <p class="label">Issued {{ now()->format('F j, Y') }}</p>
    </div>
</body>
</html>
