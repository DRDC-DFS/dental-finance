<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function panel(Request $request)
    {
        $filters = $this->resolveInventoryFilters($request);
        $typeFilter = $request->get('type');
        $activeTab = $request->get('tab', 'items');

        if (!in_array($activeTab, ['items', 'in', 'out', 'stock'], true)) {
            $activeTab = 'items';
        }

        $itemsQuery = InventoryItem::query()->orderBy('name');

        if ($this->isValidTypeFilter($typeFilter)) {
            $itemsQuery->where('type', $typeFilter);
        } else {
            $typeFilter = null;
        }

        $items = $itemsQuery->get();

        $movementsIn = $this->buildMovementQuery('IN', $filters)
            ->with('item')
            ->when($typeFilter, function ($query) use ($typeFilter) {
                $query->whereHas('item', function ($itemQuery) use ($typeFilter) {
                    $itemQuery->where('type', $typeFilter);
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $movementsOut = $this->buildMovementQuery('OUT', $filters)
            ->with('item')
            ->when($typeFilter, function ($query) use ($typeFilter) {
                $query->whereHas('item', function ($itemQuery) use ($typeFilter) {
                    $itemQuery->where('type', $typeFilter);
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $stockData = $this->buildStockData($filters, $typeFilter);

        return view('inventory.panel', [
            'items' => $items,
            'typeFilter' => $typeFilter,
            'activeTab' => $activeTab,

            'movementsIn' => $movementsIn,
            'movementsOut' => $movementsOut,

            'stockItems' => $stockData['items'],
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

    public function index(Request $request)
    {
        $typeFilter = $request->get('type');

        $query = InventoryItem::query()->orderBy('name');

        if ($this->isValidTypeFilter($typeFilter)) {
            $query->where('type', $typeFilter);
        } else {
            $typeFilter = null;
        }

        $items = $query->get();

        return view('inventory.items.index', compact('items', 'typeFilter'));
    }

    public function create()
    {
        return view('inventory.items.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:inventory_items,name'],
            'type' => ['required', 'in:barang,bahan,alat'],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Nama item wajib diisi.',
            'name.max' => 'Nama item maksimal 150 karakter.',
            'name.unique' => 'Nama item sudah ada.',
            'type.required' => 'Jenis item wajib dipilih.',
            'type.in' => 'Jenis item tidak valid.',
            'unit.required' => 'Satuan wajib diisi.',
            'unit.max' => 'Satuan maksimal 50 karakter.',
            'minimum_stock.required' => 'Minimum stok wajib diisi.',
            'minimum_stock.numeric' => 'Minimum stok harus berupa angka.',
            'minimum_stock.min' => 'Minimum stok tidak boleh kurang dari 0.',
        ]);

        InventoryItem::create([
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'unit' => trim($validated['unit']),
            'minimum_stock' => $validated['minimum_stock'],
            'is_active' => 1,
        ]);

        return redirect()
            ->route('inventory.items.index')
            ->with('success', 'Item berhasil ditambahkan.');
    }

    public function edit(InventoryItem $item)
    {
        return view('inventory.items.edit', compact('item'));
    }

    public function update(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150', 'unique:inventory_items,name,' . $item->id],
            'type' => ['sometimes', 'required', 'in:barang,bahan,alat'],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'minimum_stock' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Nama item wajib diisi.',
            'name.max' => 'Nama item maksimal 150 karakter.',
            'name.unique' => 'Nama item sudah ada.',
            'type.required' => 'Jenis item wajib dipilih.',
            'type.in' => 'Jenis item tidak valid.',
            'unit.required' => 'Satuan wajib diisi.',
            'unit.max' => 'Satuan maksimal 50 karakter.',
            'minimum_stock.required' => 'Minimum stok wajib diisi.',
            'minimum_stock.numeric' => 'Minimum stok harus berupa angka.',
            'minimum_stock.min' => 'Minimum stok tidak boleh kurang dari 0.',
            'is_active.boolean' => 'Status aktif tidak valid.',
        ]);

        $payload = [];

        if (array_key_exists('name', $validated)) {
            $payload['name'] = trim($validated['name']);
        }

        if (array_key_exists('type', $validated)) {
            $payload['type'] = $validated['type'];
        }

        if (array_key_exists('unit', $validated)) {
            $payload['unit'] = trim($validated['unit']);
        }

        if (array_key_exists('minimum_stock', $validated)) {
            $payload['minimum_stock'] = $validated['minimum_stock'];
        }

        if (array_key_exists('is_active', $validated)) {
            $payload['is_active'] = (int) $validated['is_active'];
        }

        if (!empty($payload)) {
            $item->update($payload);
        }

        return redirect()
            ->route('inventory.items.index')
            ->with('success', 'Item berhasil diperbarui.');
    }

    public function destroy(InventoryItem $item)
    {
        if (!$this->isOwner()) {
            abort(403, 'Hanya OWNER yang boleh menghapus item inventory.');
        }

        $hasMovements = InventoryMovement::where('item_id', $item->id)->exists();

        if ($hasMovements) {
            return back()->withErrors([
                'delete_item' => 'Item tidak bisa dihapus karena sudah memiliki riwayat inventory masuk/keluar.',
            ]);
        }

        $item->delete();

        return redirect()
            ->route('inventory.panel', ['tab' => 'items'])
            ->with('success', 'Item berhasil dihapus.');
    }

    private function isValidTypeFilter(?string $typeFilter): bool
    {
        return in_array($typeFilter, ['barang', 'bahan', 'alat'], true);
    }

    private function currentRole(): string
    {
        return strtolower((string) (auth()->user()->role ?? ''));
    }

    private function isOwner(): bool
    {
        return $this->currentRole() === 'owner';
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

    private function buildStockData(array $filters, ?string $typeFilter = null): array
    {
        $itemsQuery = InventoryItem::where('is_active', 1)->orderBy('name');

        if ($this->isValidTypeFilter($typeFilter)) {
            $itemsQuery->where('type', $typeFilter);
        }

        $items = $itemsQuery->get();
        $itemIds = $items->pluck('id');

        if ($filters['is_owner']) {
            $periodIn = InventoryMovement::where('type', 'IN')
                ->whereBetween('date', [$filters['date_start'], $filters['date_end']])
                ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('item_id', $itemIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
                ->selectRaw('item_id, SUM(qty) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $periodOut = InventoryMovement::where('type', 'OUT')
                ->whereBetween('date', [$filters['date_start'], $filters['date_end']])
                ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('item_id', $itemIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
                ->selectRaw('item_id, SUM(ABS(qty)) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $stockInTotal = InventoryMovement::where('type', 'IN')
                ->whereDate('date', '<=', $filters['date_end'])
                ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('item_id', $itemIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
                ->selectRaw('item_id, SUM(qty) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $stockOutTotal = InventoryMovement::where('type', 'OUT')
                ->whereDate('date', '<=', $filters['date_end'])
                ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('item_id', $itemIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
                ->selectRaw('item_id, SUM(ABS(qty)) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');
        } else {
            $periodIn = InventoryMovement::where('type', 'IN')
                ->whereDate('date', $filters['date'])
                ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('item_id', $itemIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
                ->selectRaw('item_id, SUM(qty) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $periodOut = InventoryMovement::where('type', 'OUT')
                ->whereDate('date', $filters['date'])
                ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('item_id', $itemIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
                ->selectRaw('item_id, SUM(ABS(qty)) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $stockInTotal = InventoryMovement::where('type', 'IN')
                ->whereDate('date', '<=', $filters['date'])
                ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('item_id', $itemIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
                ->selectRaw('item_id, SUM(qty) as total')
                ->groupBy('item_id')
                ->pluck('total', 'item_id');

            $stockOutTotal = InventoryMovement::where('type', 'OUT')
                ->whereDate('date', '<=', $filters['date'])
                ->when($itemIds->isNotEmpty(), fn ($q) => $q->whereIn('item_id', $itemIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
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