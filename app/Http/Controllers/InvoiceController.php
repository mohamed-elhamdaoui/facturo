<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $request->query('client_id');

        $query = Invoice::with(['client'])->withCount('items')->latest();

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $invoices = $query->paginate(10)->withQueryString();
        $clients = \App\Models\Client::orderBy('name')->get(['id', 'name']);
        $selectedClient = $clientId ? \App\Models\Client::find($clientId) : null;

        return view('invoices.index', compact('invoices', 'clients', 'selectedClient'));
    }

    public function create()
    {
        $products = Product::orderBy('category')->orderBy('name')->get();
        $clients  = \App\Models\Client::orderBy('name')->get();
        return view('invoices.create', compact('products', 'clients'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $data = $request->validated();

        $invoice = DB::transaction(function () use ($data) {
            $invoice = Invoice::create([
                'client_id' => $data['client_id'],
                'total'     => 0,
                'discount_amount' => 0,
            ]);

            $grandTotal = 0;
            $totalDiscountAmount = 0;

            foreach ($data['items'] as $itemData) {
                $product  = Product::findOrFail($itemData['product_id']);
                $subtotal = $product->price * $itemData['quantity'];
                
                $discountPercentage = isset($itemData['discount_percentage']) ? (int)$itemData['discount_percentage'] : 0;
                $rowDiscount = $subtotal * ($discountPercentage / 100);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity'   => $itemData['quantity'],
                    'discount_percentage' => $discountPercentage,
                    'unit_price' => $product->price,
                    'subtotal'   => $subtotal - $rowDiscount,
                ]);

                // Decrement product stock
                $product->decrement('stock_quantity', $itemData['quantity']);

                // Create stock movement
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'type'       => 'sortie',
                    'quantity'   => $itemData['quantity'],
                    'reason'     => 'Facture #' . $invoice->id,
                ]);

                $grandTotal += $subtotal;
                $totalDiscountAmount += $rowDiscount;
            }

            $invoice->update([
                'discount_amount' => $totalDiscountAmount,
                'total'           => $grandTotal - $totalDiscountAmount, // Total Net to pay
            ]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
                         ->with('success', 'Facture créée avec succès.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items.product']);
        return view('invoices.show', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        $invoice->load(['client', 'items.product']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download('invoice-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) . '.pdf');
    }

    public function destroy(Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            // Restore stock for each item before deleting
            foreach ($invoice->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_quantity', $item->quantity);
                    
                    \App\Models\StockMovement::create([
                        'product_id' => $item->product_id,
                        'type'       => 'entrée',
                        'quantity'   => $item->quantity,
                        'reason'     => 'Annulation Facture #' . $invoice->id,
                    ]);
                }
            }
            $invoice->delete();
        });

        return redirect()->route('invoices.index')->with('success', 'Facture supprimée avec succès.');
    }
}
