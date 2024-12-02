<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Muestra una lista de todos los pagos.
     */
    public function index()
    {
        try {
            $pagos = Payment::all();
            return response()->json($pagos);
        } catch (\Exception $e) {
            Log::error('Error al obtener pagos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener pagos'], 500);
        }
    }

    /**
     * Almacena un nuevo pago en la base de datos.
     */
    public function store(Request $request)
    {
        try {
            $datosValidados = $request->validate([
                'order_id' => 'required|string',
                'status' => 'required|string',
                'payment_method' => 'required|string',
                'amount' => 'required|numeric',
                'currency' => 'required|string',
            ]);

            $pago = Payment::create($datosValidados);
            return response()->json($pago, 201);
        } catch (\Exception $e) {
            Log::error('Error al crear pago: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear pago'], 500);
        }
    }

    /**
     * Muestra un pago específico.
     */
    public function show(string $id)
    {
        try {
            $pago = Payment::findOrFail($id);
            return response()->json($pago);
        } catch (\Exception $e) {
            Log::error('Error al obtener pago: ' . $e->getMessage());
            return response()->json(['error' => 'Pago no encontrado'], 404);
        }
    }

    /**
     * Actualiza un pago específico en la base de datos.
     */
    public function update(Request $request, string $id)
    {
        try {
            $pago = Payment::findOrFail($id);
            $datosValidados = $request->validate([
                'status' => 'string',
                'payment_method' => 'string',
                'amount' => 'numeric',
                'currency' => 'string',
            ]);

            $pago->update($datosValidados); 
            return response()->json($pago);
        } catch (\Exception $e) {
            Log::error('Error al actualizar pago: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar pago'], 500);
        }
    }

    /**
     * Elimina un pago específico de la base de datos.
     */
    public function destroy(string $id)
    {
        try {
            $pago = Payment::findOrFail($id);
            $pago->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error al eliminar pago: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar pago'], 500);
        }
    }
}
