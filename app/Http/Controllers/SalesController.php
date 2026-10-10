<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', 'today');
        [$start, $end] = $this->rangeDates($range);

        $receipts = $this->issuedReceipts($start, $end);

        $totalSales = (float) $receipts->sum(fn ($r) => (float) $r->Total_Amount);
        $orderCount = $receipts->count();
        $avgTicket  = $orderCount > 0 ? $totalSales / $orderCount : 0;

        $recentReceipts = $receipts->take(10);

        return view('pos.sales', compact(
            'totalSales', 'orderCount', 'avgTicket', 'recentReceipts', 'range'
        ));
    }

    public function export(Request $request)
    {
        $range = $request->get('range', 'today');
        [$start, $end] = $this->rangeDates($range);

        $receipts   = $this->issuedReceipts($start, $end)->load('payment', 'order.employee', 'order.orderItems.menu');
        $totalSales = (float) $receipts->sum(fn ($r) => (float) $r->Total_Amount);
        $orderCount = $receipts->count();
        $avgTicket  = $orderCount > 0 ? $totalSales / $orderCount : 0;
        $rangeLabel = match ($range) {
            'week'  => 'This Week',
            'month' => 'This Month',
            default => 'Today',
        };

        $generatedBy = session('pos_employee');

        $html = view('pos.sales-pdf', compact(
            'receipts', 'totalSales', 'orderCount', 'avgTicket',
            'range', 'rangeLabel', 'start', 'end', 'generatedBy'
        ))->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape');

        $filename = 'sales_' . $range . '_' . \Carbon\Carbon::today()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function rangeDates(string $range): array
    {
        return match ($range) {
            'week'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
        };
    }

    private function issuedReceipts(Carbon $start, Carbon $end)
    {
        return Receipt::with(['order', 'payment'])
            ->where('Status', 'issued')
            ->whereDate('Date', '>=', $start->toDateString())
            ->whereDate('Date', '<=', $end->toDateString())
            ->orderByDesc('created_at')
            ->get();
    }
}
