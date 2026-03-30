<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    /** MASTER ITEM */
    public function itemsIndex()
    {
        $items = InventoryItem::orderBy('name')->get();
        return view('inventory.items_index', compact('items'));
    }

    public function itemsCreate()
    {
        return view('inventory.items_create');
    }

    public function itemsStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'opening_stock' => ['required', 'numeric', 'min:0'],
        ]);

        InventoryItem::create([
            'name' => $data['name'],
            'unit' => $data['unit'],
            'opening_stock' => $data['opening_stock'],
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('inv.items.index')->with('success', 'Item berhasil ditambahkan.');
    }

    /** MASUK */
    public function inIndex()
    {
        $rows = InventoryMovement::with('item')
            ->where('move_type', 'IN')
            ->orderByDesc('move_date')
            ->orderByDesc('id')
            ->get();

        return view('inventory.in_index', compact('rows'));
    }

    public function inCreate()
    {
        $items = InventoryItem::where('is_active', true)->orderBy('name')->get();
        return view('inventory.in_create', compact('items'));
    }

    public function inStore(Request $request)
    {
        $data = $request->validate([
            'move_date' => ['required', 'date'],
            'item_id' => ['required', 'exists:inventory_items,id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        InventoryMovement::create([
            'move_date' => $data['move_date'],
            'item_id' => $data['item_id'],
            'move_type' => 'IN',
            'qty' => $data['qty'],
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('inv.in.index')->with('success', 'Barang masuk berhasil disimpan.');
    }

    /** KELUAR */
    public function outIndex()
    {
        $rows = InventoryMovement::with('item')
            ->where('move_type', 'OUT')
            ->orderByDesc('move_date')
            ->orderByDesc('id')
            ->get();

        return view('inventory.out_index', compact('rows'));
    }

    public function outCreate()
    {
        $items = InventoryItem::where('is_active', true)->orderBy('name')->get();
        return view('inventory.out_create', compact('items'));
    }

    public function outStore(Request $request)
    {
        $data = $request->validate([
            'move_date' => ['required', 'date'],
            'item_id' => ['required', 'exists:inventory_items,id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // validasi stok tidak boleh minus
        $stock = $this->getStockByItemId($data['item_id']);
        if ($data['qty'] > $stock) {
            throw ValidationException::withMessages([
                'qty' => 'Stok tidak cukup. Stok saat ini: ' . $stock,
            ]);
        }

        InventoryMovement::create([
            'move_date' => $data['move_date'],
            'item_id' => $data['item_id'],
            'move_type' => 'OUT',
            'qty' => $data['qty'],
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('inv.out.index')->with('success', 'Barang keluar berhasil disimpan.');
    }

    /** HAPUS MOVEMENT (admin hanya boleh hapus jika hari yang sama) */
    public function movementDelete(Request $request, InventoryMovement $movement)
    {
        $isOwner = $request->user()->role === 'OWNER';
        $today = date('Y-m-d');

        if (! $isOwner) {
            if ($movement->move_date !== $today) {
                abort(403);
            }
        }

        $movement->delete();

        return back()->with('success', 'Transaksi inventori berhasil dihapus.');
    }

    /** LAPORAN STOK */
    public function stock()
    {
        $items = InventoryItem::orderBy('name')->get();

        $in = InventoryMovement::select('item_id', DB::raw("SUM(qty) as qty_in"))
            ->where('move_type', 'IN')
            ->groupBy('item_id')
            ->pluck('qty_in', 'item_id')
            ->toArray();

        $out = InventoryMovement::select('item_id', DB::raw("SUM(qty) as qty_out"))
            ->where('move_type', 'OUT')
            ->groupBy('item_id')
            ->pluck('qty_out', 'item_id')
            ->toArray();

        return view('inventory.stock', compact('items', 'in', 'out'));
    }

    private function getStockByItemId(int $itemId): float
    {
        $item = InventoryItem::findOrFail($itemId);

        $in = (float) InventoryMovement::where('item_id', $itemId)->where('move_type', 'IN')->sum('qty');
        $out = (float) InventoryMovement::where('item_id', $itemId)->where('move_type', 'OUT')->sum('qty');

        return (float)$item->opening_stock + $in - $out;
    }
}