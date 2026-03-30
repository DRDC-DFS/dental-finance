<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseMovementController extends Controller
{
    private function ensureOwner(): void
    {
        $user = Auth::user();

        if (!$user || strtolower((string) ($user->role ?? '')) !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh mengakses modul Gudang.');
        }
    }

    public function index(string $type)
    {
        $this->ensureOwner();

        $typeUpper = strtoupper($type);

        $items = WarehouseItem::where('is_active', 1)->pluck('name', 'id');

        $movements = WarehouseMovement::with('item')
            ->where('type', $typeUpper)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view("warehouse.movements.{$type}_index", [
            'title' => 'Gudang ' . strtoupper($type),
            'type' => $type,
            'items' => $items,
            'movements' => $movements,
        ]);
    }

    public function create(string $type)
    {
        $this->ensureOwner();

        $typeUpper = strtoupper($type);

        $items = WarehouseItem::where('is_active', 1)->pluck('name', 'id');

        $movements = WarehouseMovement::with('item')
            ->where('type', $typeUpper)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return view("warehouse.movements.{$type}_create", [
            'title' => 'Tambah Gudang ' . strtoupper($type),
            'type' => $type,
            'items' => $items,
            'movements' => $movements,
        ]);
    }

    public function edit(string $type, int $id)
    {
        $this->ensureOwner();

        $typeUpper = strtoupper($type);

        $movement = WarehouseMovement::with('item')->findOrFail($id);

        if ($movement->type !== $typeUpper) {
            abort(404);
        }

        $items = WarehouseItem::where('is_active', 1)->pluck('name', 'id');

        $movements = WarehouseMovement::with('item')
            ->where('type', $typeUpper)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return view("warehouse.movements.{$type}_create", [
            'title' => 'Edit Gudang ' . strtoupper($type),
            'type' => $type,
            'items' => $items,
            'movements' => $movements,
            'movement' => $movement,
        ]);
    }

    public function store(Request $request, string $type)
    {
        $this->ensureOwner();

        return $this->saveMovement($request, $type, null);
    }

    public function update(Request $request, string $type, int $id)
    {
        $this->ensureOwner();

        return $this->saveMovement($request, $type, $id);
    }

    private function saveMovement(Request $request, string $type, ?int $id)
    {
        $type = strtolower($type);
        $typeUpper = strtoupper($type);

        $request->validate([
            'item_id' => 'required|exists:warehouse_items,id',
            'qty' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
        ]);

        $itemId = (int) $request->item_id;
        $qtyInput = (float) $request->qty;

        $sumQuery = WarehouseMovement::where('item_id', $itemId);
        if ($id) {
            $sumQuery->where('id', '!=', $id);
        }
        $currentStock = (float) $sumQuery->sum('qty');

        if ($type === 'in') {
            $qty = abs($qtyInput);
        } elseif ($type === 'out') {
            if ($qtyInput > $currentStock) {
                return back()
                    ->withErrors(['qty' => 'Stok gudang tidak cukup. Stok tersedia: ' . number_format($currentStock, 2, ',', '.')])
                    ->withInput();
            }

            $qty = -abs($qtyInput);
        } else {
            abort(404);
        }

        if ($id) {
            $movement = WarehouseMovement::findOrFail($id);

            if ($movement->type !== $typeUpper) {
                abort(404);
            }

            $movement->update([
                'item_id' => $itemId,
                'qty' => $qty,
                'date' => $request->date,
                'reference' => $request->reference,
                'notes' => $request->notes,
            ]);

            return redirect()
                ->route('warehouse.movements.index', ['type' => $type])
                ->with('success', 'Data gudang berhasil diupdate.');
        }

        WarehouseMovement::create([
            'item_id' => $itemId,
            'type' => $typeUpper,
            'qty' => $qty,
            'date' => $request->date,
            'reference' => $request->reference,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('warehouse.movements.index', ['type' => $type])
            ->with('success', 'Data gudang berhasil disimpan.');
    }

    public function destroy(string $type, int $id)
    {
        $this->ensureOwner();

        $typeUpper = strtoupper($type);

        $movement = WarehouseMovement::findOrFail($id);

        if ($movement->type !== $typeUpper) {
            abort(404);
        }

        $newStock = (float) WarehouseMovement::where('item_id', $movement->item_id)
            ->where('id', '!=', $movement->id)
            ->sum('qty');

        if ($newStock < 0) {
            return back()->withErrors([
                'delete' => 'Tidak bisa dihapus karena akan membuat stok gudang minus.'
            ]);
        }

        $movement->delete();

        return redirect()
            ->route('warehouse.movements.index', ['type' => strtolower($typeUpper)])
            ->with('success', 'Data gudang berhasil dihapus.');
    }

    public function stok()
    {
        $this->ensureOwner();

        $items = WarehouseItem::where('is_active', 1)
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

        $alertItems = $items->map(function ($item) use ($in, $out) {
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

        return view('warehouse.stok', [
            'items' => $items,
            'in' => $in,
            'out' => $out,
            'alertItems' => $alertItems,
        ]);
    }
}