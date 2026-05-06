<x-filament-panels::page>
    <style>
        .fi-body {
            color: black;
        }
        
        .report-grid {
            display: grid;
            gap: 20px;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .report-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 18px;
            font-weight: 600;
        }

        .report-table-wrap {
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .report-table th {
            text-align: left;
            padding: 12px 16px;
            font-weight: 600;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .report-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .report-table tbody tr:hover {
            background: #f8fafc;
        }

        .report-empty {
            padding: 20px;
            color: #6b7280;
        }

        .report-money {
            font-weight: 600;
            white-space: nowrap;
        }

        .report-month {
            white-space: nowrap;
        }
    </style>
    <div class="report-grid">
        <div class="report-card">
            <div class="report-card-header">Выручка по месяцам</div>

            <div class="report-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Месяц</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->monthlyRevenue as $row)
                            <tr>
                                <td class="report-month">
                                    {{ \Carbon\Carbon::parse($row->month)->format('m.Y') }}
                                </td>
                                <td class="report-money">
                                    {{ number_format((float) $row->total_revenue, 2, ',', ' ') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="report-empty">Нет данных</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-card">
            <div class="report-card-header">Игроки по месяцам</div>

            <div class="report-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Месяц</th>
                            <th>Ник</th>
                            <th>Имя</th>
                            <th>Фамилия</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->playerMonthlyRevenue as $row)
                            <tr>
                                <td class="report-month">
                                    {{ \Carbon\Carbon::parse($row->month)->format('m.Y') }}
                                </td>
                                <td>{{ $row->nickname }}</td>
                                <td>{{ $row->first_name ?: '—' }}</td>
                                <td>{{ $row->last_name ?: '—' }}</td>
                                <td class="report-money">
                                    {{ number_format((float) $row->total_paid, 2, ',', ' ') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="report-empty">Нет данных</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>