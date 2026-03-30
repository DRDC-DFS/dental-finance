<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function index(Request $request, string $type)
    {
        $typeUpper = $this->resolveMovementType($type);
        $filters = $this->resolveInventoryFilters($request);

        $items = InventoryItem::where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id');

        $movements = $this->buildMovementQuery($typeUpper, $filters)
            ->with('item')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view("inventory.movements.{$type}_index", [
            'title' => 'Data Inventaris ' . strtoupper($type),
            'type' => $type,
            'items' => $items,
            'movements' => $movements,

            'isOwner' => $filters['is_owner'],
            'filterDate' => $filters['date'],
            'dateStart' => $filters['date_start'],
            'dateEnd' => $filters['date_end'],
            'periodLabel' => $filters['label'],
        ]);
    }

    public function create(string $type)
    {
        $typeUpper = $this->resolveMovementType($type);
        $type = strtolower($type);

        $items = InventoryItem::where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id');

        $movements = InventoryMovement::with('item')
            ->where('type', $typeUpper)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return view("inventory.movements.{$type}_create", [
            'title' => 'Tambah Data Inventaris ' . strtoupper($type),
            'type' => $type,
            'items' => $items,
            'movements' => $movements,
        ]);
    }

    public function edit(string $type, int $id)
    {
        $typeUpper = $this->resolveMovementType($type);
        $type = strtolower($type);

        $movement = InventoryMovement::with('item')->findOrFail($id);

        if ($movement->type !== $typeUpper) {
            abort(404);
        }

        $items = InventoryItem::where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id');

        $movements = InventoryMovement::with('item')
            ->where('type', $typeUpper)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return view("inventory.movements.{$type}_create", [
            'title' => 'Edit Data Inventaris ' . strtoupper($type),
            'type' => $type,
            'items' => $items,
            'movements' => $movements,
            'movement' => $movement,
        ]);
    }

    public function store(Request $request, string $type)
    {
        return $this->saveMovement($request, $type, null);
    }

    public function update(Request $request, string $type, int $id)
    {
        return $this->saveMovement($request, $type, $id);
    }

    private function saveMovement(Request $request, string $type, ?int $id)
    {
        $type = strtolower($type);
        $typeUpper = $this->resolveMovementType($type);

        $request->validate([
            'item_id' => ['required', 'exists:inventory_items,id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'item_id.required' => 'Item wajib dipilih.',
            'item_id.exists' => 'Item tidak ditemukan.',
            'qty.required' => 'Qty wajib diisi.',
            'qty.numeric' => 'Qty harus berupa angka.',
            'qty.min' => 'Qty minimal 0,01.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.date' => 'Tanggal tidak valid.',
            'reference.max' => 'Reference maksimal 150 karakter.',
            'notes.max' => 'Notes maksimal 500 karakter.',
        ]);

        $itemId = (int) $request->item_id;
        $qtyInput = (float) $request->qty;

        $movement = null;
        if ($id !== null) {
            $movement = InventoryMovement::findOrFail($id);

            if ($movement->type !== $typeUpper) {
                abort(404);
            }
        }

        if ($type === 'in') {
            $qty = abs($qtyInput);
        } elseif ($type === 'out') {
            $availableStock = $this->calculateAvailableStockForOut($itemId, $movement);

            if ($qtyInput > $availableStock) {
                return back()
                    ->withErrors([
                        'qty' => 'Stok tidak cukup. Stok tersedia: ' . number_format($availableStock, 2, ',', '.'),
                    ])
                    ->withInput();
            }

            $qty = -abs($qtyInput);
        } else {
            $qty = (float) $qtyInput;
        }

        $payload = [
            'item_id' => $itemId,
            'qty' => $qty,
            'date' => $request->date,
            'reference' => $request->reference,
            'notes' => $request->notes,
        ];

        if ($movement) {
            $movement->update($payload);

            return redirect()
                ->route('inventory.movements.index', ['type' => $type])
                ->with('success', 'Data berhasil diupdate.');
        }

        InventoryMovement::create($payload + [
            'type' => $typeUpper,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('inventory.movements.index', ['type' => $type])
            ->with('success', 'Data berhasil disimpan.');
    }

    public function destroy(string $type, int $id)
    {
        $typeUpper = $this->resolveMovementType($type);

        $movement = InventoryMovement::findOrFail($id);

        if ($movement->type !== $typeUpper) {
            abort(404);
        }

        $newStock = (float) InventoryMovement::where('item_id', $movement->item_id)
            ->where('id', '!=', $movement->id)
            ->sum('qty');

        if ($newStock < 0) {
            return back()->withErrors([
                'delete' => 'Tidak bisa dihapus karena akan membuat stok minus.',
            ]);
        }

        $movement->delete();

        return redirect()
            ->route('inventory.movements.index', ['type' => strtolower($typeUpper)])
            ->with('success', 'Data berhasil dihapus.');
    }

    public function stok(Request $request)
    {
        $filters = $this->resolveInventoryFilters($request);
        $stockData = $this->buildStockData($filters);

        return view('inventory.stok', [
            'items' => $stockData['items'],
            'periodIn' => $stockData['periodIn'],
            'periodOut' => $stockData['periodOut'],
            'stockEnd' => $stockData['stockEnd'],
            'alertItems' => $stockData['alertItems'],

            'isOwner' => $filters['is_owner'],
            'filterDate' => $filters['date'],
            'dateStart' => $filters['date_start'],
            'dateEnd' => $filters['date_end'],
            'periodLabel' => $filters['label'],
        ]);
    }

    public function exportPdf(Request $request, string $type)
    {
        $type = strtolower($type);
        $typeUpper = $this->resolveMovementType($type);
        $filters = $this->resolveInventoryFilters($request);

        $movements = $this->buildMovementQuery($typeUpper, $filters)
            ->with('item')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $title = $type === 'in' ? 'Data Inventaris Masuk' : 'Data Inventaris Keluar';

        $pdf = Pdf::loadView('inventory.exports.movements_pdf', [
            'title' => $title,
            'type' => $type,
            'movements' => $movements,
            'periodLabel' => $filters['label'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('data-inventaris-' . $type . '-' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request, string $type)
    {
        $type = strtolower($type);
        $typeUpper = $this->resolveMovementType($type);
        $filters = $this->resolveInventoryFilters($request);

        $movements = $this->buildMovementQuery($typeUpper, $filters)
            ->with('item')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $title = $type === 'in' ? 'Data Inventaris Masuk' : 'Data Inventaris Keluar';
        $filename = 'data-inventaris-' . $type . '-' . now()->format('Ymd_His') . '.xls';

        return response()
            ->view('inventory.exports.movements_excel', [
                'title' => $title,
                'type' => $type,
                'movements' => $movements,
                'periodLabel' => $filters['label'],
            ])
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportStokPdf(Request $request)
    {
        $filters = $this->resolveInventoryFilters($request);
        $stockData = $this->buildStockData($filters);

        $pdf = Pdf::loadView('inventory.exports.stok_pdf', [
            'items' => $stockData['items'],
            'periodIn' => $stockData['periodIn'],
            'periodOut' => $stockData['periodOut'],
            'stockEnd' => $stockData['stockEnd'],
            'periodLabel' => $filters['label'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('data-inventaris-stok-' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportStokExcel(Request $request)
    {
        $filters = $this->resolveInventoryFilters($request);
        $stockData = $this->buildStockData($filters);
        $filename = 'data-inventaris-stok-' . now()->format('Ymd_His') . '.xls';

        return response()
            ->view('inventory.exports.stok_excel', [
                'items' => $stockData['items'],
                'periodIn' => $stockData['periodIn'],
                'periodOut' => $stockData['periodOut'],
                'stockEnd' => $stockData['stockEnd'],
                'periodLabel' => $filters['label'],
            ])
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function currentRole(): string
    {
        return strtolower((string) (auth()->user()->role ?? ''));
    }

    private function isOwner(): bool
    {
        return $this->currentRole() === 'owner';
    }

    private function resolveMovementType(string $type): string
    {
        $typeUpper = strtoupper(trim($type));

        if (!in_array($typeUpper, ['IN', 'OUT', 'ADJUST'], true)) {
            abort(404);
        }

        return $typeUpper;
    }

    private function resolveInventoryFilters(Request $request): array
    {
        $today = now()->toDateString();
        $isOwner = $this->isOwner();

        if ($isOwner) {
            $validated = $request->validate([
                'date_start' => ['nullable', 'date'],
                'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            ], [
                'date_start.date' => 'Tanggal mulai tidak valid.',
                'date_end.date' => 'Tanggal selesai tidak valid.',
                'date_end.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]);

            $dateStart = $validated['date_start'] ?? $today;
            $dateEnd = $validated['date_end'] ?? $dateStart;

            return [
                'is_owner' => true,
                'date' => null,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'label' => $dateStart === $dateEnd
                    ? 'Tanggal ' . date('d-m-Y', strtotime($dateStart))
                    : 'Periode ' . date('d-m-Y', strtotime($dateStart)) . ' s/d ' . date('d-m-Y', strtotime($dateEnd)),
            ];
        }

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ], [
            'date.date' => 'Tanggal tidak valid.',
        ]);

        $date = $validated['date'] ?? $today;

        return [
            'is_owner' => false,
            'date' => $date,
            'date_start' => null,
            'date_end' => null,
            'label' => 'Tanggal ' . date('d-m-Y', strtotime($date)),
        ];
    }

    private function buildMovementQuery(string $type, array $filters)
    {
        $query = InventoryMovement::query()->where('type', $type);

        if ($filters['is_owner']) {
            $query->whereBetween('date', [$filters['date_start'], $filters['date_end']]);
        } else {
            $query->whereDate('date', $filters['date']);
        }

        return $query;
    }

    private function calculateAvailableStockForOut(int $itemId, ?InventoryMovement $editingMovement = null): float
    {
        $baseStock = (float) InventoryMovement::where('item_id', $itemId)->sum('qty');

        if ($editingMovement && (int) $editingMovement->item_id === $itemId) {
            return $baseStock + abs((float) $editingMovement->qty);
        }

        return $baseStock;
    }

    private function buildStockData(array $filters): array
    {
        $items = InventoryItem::where('is_active', 1)
            ->orderBy('name')
            ->get();

        if ($filters['is_owner']) {
            $periodIn = InventoryMovement::where('type', 'IN')
                ->whereBetween('date', [$filters['date_start'], $filters['date_end']])
                ->selectRaw('item_id, SUM(qty) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $periodOut = InventoryMovement::where('type', 'OUT')
                ->whereBetween('date', [$filters['date_start'], $filters['date_end']])
                ->selectRaw('item_id, SUM(ABS(qty)) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $stockInTotal = InventoryMovement::where('type', 'IN')
                ->whereDate('date', '<=', $filters['date_end'])
                ->selectRaw('item_id, SUM(qty) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $stockOutTotal = InventoryMovement::where('type', 'OUT')
                ->whereDate('date', '<=', $filters['date_end'])
                ->selectRaw('item_id, SUM(ABS(qty)) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');
        } else {
            $periodIn = InventoryMovement::where('type', 'IN')
                ->whereDate('date', $filters['date'])
                ->selectRaw('item_id, SUM(qty) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $periodOut = InventoryMovement::where('type', 'OUT')
                ->whereDate('date', $filters['date'])
                ->selectRaw('item_id, SUM(ABS(qty)) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $stockInTotal = InventoryMovement::where('type', 'IN')
                ->whereDate('date', '<=', $filters['date'])
                ->selectRaw('item_id, SUM(qty) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $stockOutTotal = InventoryMovement::where('type', 'OUT')
                ->whereDate('date', '<=', $filters['date'])
                ->selectRaw('item_id, SUM(ABS(qty)) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');
        }

        $stockEnd = [];
        $alertItems = $items->map(function ($item) use ($stockInTotal, $stockOutTotal, &$stockEnd) {
            $qtyInTotal = (float) ($stockInTotal[$item->id] ?? 0);
            $qtyOutTotal = (float) ($stockOutTotal[$item->id] ?? 0);
            $stock = $qtyInTotal - $qtyOutTotal;
            $minimum = (float) ($item->minimum_stock ?? 0);

            $stockEnd[$item->id] = $stock;

            $item->current_stock = $stock;
            $item->minimum_stock_value = $minimum;
            $item->is_below_minimum = $minimum > 0 && $stock < $minimum;
            $item->is_at_minimum = $minimum > 0 && $stock == $minimum;
            $item->is_minimum_alert = $minimum > 0 && $stock <= $minimum;

            return $item;
        })->filter(function ($item) {
            return $item->is_minimum_alert;
        })->values();

        return [
            'items' => $items,
            'periodIn' => $periodIn,
            'periodOut' => $periodOut,
            'stockEnd' => $stockEnd,
            'alertItems' => $alertItems,
        ];
    }
}