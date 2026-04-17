<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Department Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1 { margin: 0 0 8px 0; }
        p { margin: 0 0 16px 0; font-size: 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Department Analytics Report</h1>
    <p>Rentang tanggal: {{ $dateFrom ?: '-' }} s/d {{ $dateTo ?: '-' }}</p>
    <table>
        <thead>
            <tr>
                <th>Nama Departemen</th>
                <th>Total Responden</th>
                <th>Tingkat Partisipasi (%)</th>
                <th>Rata-rata Skor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ (int) $row->total_respondents }}</td>
                    <td>{{ number_format((float) $row->participation_rate, 2) }}%</td>
                    <td>{{ number_format((float) $row->average_score, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
