<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { margin-bottom: 0; }
        .subtitle { color: #555; margin-bottom: 10px; }
        .summary { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>

<h1>{{ $meta['title'] }}</h1>

@if($meta['subtitle'])
<div class="subtitle">{{ $meta['subtitle'] }}</div>
@endif

<div class="summary">
    Total Logs: <strong>{{ $meta['total'] }}</strong><br>
    High / Critical: <strong>{{ $meta['highCritical'] }}</strong>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>System</th>
            <th>Title</th>
            <th>Type</th>
            <th>Impact</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->logged_at }}</td>
                <td>{{ $log->system->name }}</td>
                <td>{{ $log->title }}</td>
                <td>{{ ucfirst($log->type) }}</td>
                <td>{{ ucfirst($log->impact) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>