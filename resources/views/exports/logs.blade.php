<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

<style>
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color: #333;
}

.header {
    margin-bottom: 15px;
}

h1 {
    font-size: 20px;
    margin: 0;
}

.subtitle {
    color: #666;
    margin-top: 3px;
}

.summary {
    margin-top: 10px;
    margin-bottom: 15px;
    padding: 8px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
}

.summary strong {
    font-weight: bold;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #f3f4f6;
}

th {
    text-align: left;
    font-weight: bold;
    padding: 6px;
    border: 1px solid #ddd;
}

td {
    padding: 6px;
    border: 1px solid #ddd;
}

tbody tr:nth-child(even) {
    background: #fafafa;
}

.footer {
    margin-top: 20px;
    font-size: 10px;
    color: #777;
}
</style>

</head>

<body>

<div class="header">
    <h1>{{ $meta['title'] }}</h1>

    @if($meta['subtitle'])
        <div class="subtitle">
            {{ $meta['subtitle'] }}
        </div>
    @endif
</div>

<div class="summary">
    Total Logs: <strong>{{ $meta['total'] }}</strong><br>
    High / Critical Events: <strong>{{ $meta['highCritical'] }}</strong>
</div>

<table>
<thead>
<tr>
    <th width="110">Date</th>
    <th width="140">System</th>
    <th>Title</th>
    <th width="80">Type</th>
    <th width="80">Impact</th>
</tr>
</thead>

<tbody>

@forelse($logs as $log)

<tr>
    <td>{{ \Carbon\Carbon::parse($log->logged_at)->format('d M Y') }}</td>
    <td>{{ $log->system->name }}</td>
    <td>{{ $log->title }}</td>
    <td>{{ ucfirst($log->type) }}</td>
    <td>{{ ucfirst($log->impact) }}</td>
</tr>

@empty

<tr>
    <td colspan="5" style="text-align:center; padding:10px;">
        No logs found for this report.
    </td>
</tr>

@endforelse

</tbody>
</table>

<div class="footer">
    Generated at {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>