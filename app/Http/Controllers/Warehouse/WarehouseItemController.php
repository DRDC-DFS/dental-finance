<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseItemController extends Controller
{
    private function ensureOwner(): void
    {
        $user = Auth::user();

        if (!$user || strtolower((string) ($user->role ?? '')) !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh mengakses modul Gudang.');
        }
    }

    public function panel()
    {
        $this->ensureOwner();

        $items = WarehouseItem::query()
            ->orderBy('name')
            ->get();

        $movementsIn = WarehouseMovement::with('item')
            ->where('type', 'IN')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $movementsOut = WarehouseMovement::with('item')
            ->where('type', 'OUT')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $allActiveItems = WarehouseItem::where('is_active', 1)
            ->orderBy('name')
            ->get();

        $in = WarehouseMovement::where('type', 'IN')
            ->selectRaw('item_id, SUM(qty) as total')
            ->groupBy('item_id')
            ->pluck('total', 'item_id');

        $out = WarehouseMovement::where('type', 'OUT')
            ->selectRaw('item_id, SUM(ABS(qty)) as total')
            ->groupBy('item_id')
            ->pluck('total', 'item_id');

        $alertItems = $allActiveItems->map(function ($item) use ($in, $out) {
            $qtyIn = (float) ($in[$item->id] ?? 0);
            $qtyOut = (float) ($out[$item->id] ?? 0);
            $stock = $qtyIn - $qtyOut;
            $minimum = (float) ($item->minimum_stock ?? 0);

            $item->current_stock = $stock;
            $item->minimum_stock_value = $minimum;
            $item->is_below_minimum = $minimum > 0 && $stock < $minimum;
            $item->is_at_minimum = $minimum > 0 && $stock == $minimum;
            $item->is_minimum_alert = $minimum > 0 && $stock <= $minimum;

            return $item;
        })->filter(function ($item) {
            return $item->is_minimum_alert;
        })->values();

        return view('warehouse.panel', [
            'items' => $items,
            'movementsIn' => $movementsIn,
            'movementsOut' => $movementsOut,
            'stockItems' => $allActiveItems,
            'in' => $in,
            'out' => $out,
            'alertItems' => $alertItems,
        ]);
    }

    public function index()
    {
        $this->ensureOwner();

        $items = WarehouseItem::query()
            ->orderBy('name')
            ->get();

        return view('warehouse.items.index', compact('items'));
    }

    public function create()
    {
        $this->ensureOwner();

        return view('warehouse.items.create');
    }

    public function store(Request $request)
    {
        $this->ensureOwner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:warehouse_items,name'],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
        ]);

        WarehouseItem::create([
            'name' => trim($validated['name']),
            'unit' => trim($validated['unit']),
            'minimum_stock' => $validated['minimum_stock'],
            'is_active' => 1,
        ]);

        return redirect()->route('warehouse.items.index')->with('success', 'Item gudang berhasil ditambahkan.');
    }

    public function edit(WarehouseItem $item)
    {
        $this->ensureOwner();

        return view('warehouse.items.edit', compact('item'));
    }

    public function update(Request $request, WarehouseItem $item)
    {
        $this->ensureOwner();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150', 'unique:warehouse_items,name,' . $item->id],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'minimum_stock' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $payload = [];

        if (array_key_exists('name', $validated)) {
            $payload['name'] = trim($validated['name']);
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

        return redirect()->route('warehouse.items.index')->with('success', 'Item gudang berhasil diperbarui.');
    }
}