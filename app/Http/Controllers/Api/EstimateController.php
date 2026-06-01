<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Estimate;
use App\Models\EstimateItem;

class EstimateController extends Controller
{
    //La réception publique d'une demande de devis (POST /api/estimates).
    public function store(Request $request)
    {
        \Log::debug('EstimateController.store payload', $request->all());
        $validated = $request->validate([
            'client_name'         => 'required|string|max:255',
            'client_email'        => 'required|email|max:255',
            'client_phone'        => 'nullable|string|max:20',
            'estimate_date'       => 'required|date',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $totalAmount = collect($validated['items'])
            ->sum(fn($item) => $item['quantity'] * $item['unit_price']);

        $estimate = Estimate::create([
            'client_name'   => $validated['client_name'],
            'client_email'  => $validated['client_email'],
            'client_phone'  => $validated['client_phone'] ?? null,
            'estimate_date' => $validated['estimate_date'],
            'total_amount'  => $totalAmount,
        ]);

        foreach ($validated['items'] as $item) {
            $estimate->items()->create([
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
            ]);
        }

        return response()->json([
            'message'     => 'Estimate created successfully',
            'estimate_id' => $estimate->id,
            'total_amount' => $estimate->total_amount,
        ], 201);
    }

    //La consultation sécurisée des devis par l'administrateur (GET /api/estimates).
    public function index()
    {
        $estimates = Estimate::with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($estimates);
    }

    //La mise à jour du statut d'un devis (PATCH /api/estimates/{id}/status).
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,approved,rejected',
        ]);

        $estimate = Estimate::findOrFail($id);
        $estimate->status = $validated['status'];
        $estimate->save();

        return response()->json(['message' => 'Estimate status updated successfully']);
    }

    //La suppression d'un devis.
    public function destroy($id)
    {
        $estimate = Estimate::findOrFail($id);
        $estimate->delete();

        return response()->json(['message' => 'Estimate deleted successfully']);
    }
}
