<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    /**
     * Muestra una lista de todos los pedidos.
     */
    public function index()
    {
        try {
            $pedidos = Order::with('user', 'payment')->get();
            return response()->json($pedidos);
        } catch (\Exception $e) {
            Log::error('Error al obtener pedidos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener pedidos'], 500);
        }
    }

    /**
     * Almacena un nuevo pedido en la base de datos.
     */
    public function store(Request $request)
    {
        try {
            Log::info('Iniciando creación de pedido', ['request' => $request->all()]);
            
            $this->validateOrderData($request);
            Log::info('Validación exitosa');
            
            $order = $this->createOrder($request);
            Log::info('Orden creada', ['order_id' => $order->id]);
            
            $payment = $this->processPayment($request, $order);
            Log::info('Pago procesado', ['payment' => $payment]);
            
            $this->updateOrderWithPayment($order, $payment);
            Log::info('Orden actualizada con información de pago');
            
            return $this->successResponse($order);
        } catch (\Exception $e) {
            Log::error('Error en store', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse($e);
        }
    }

    private function validateOrderData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'currency' => 'required|string'
        ]);

        if ($validator->fails()) {
            throw new \Exception('Error de validación: ' . json_encode($validator->errors()));
        }
    }

    private function createOrder(Request $request)
    {
        try {
            // Verificamos que el modelo Order esté disponible
            Log::info('Verificando modelo Order');
            
            $orderData = [
                'user_id' => $request->user_id,
                'status' => 'pendiente',
                'total' => $request->total,
                'payment_id' => null
            ];
            
            // Log de los datos que intentamos insertar
            Log::info('Intentando crear orden con datos:', $orderData);
            
            // Verificamos la conexión a la base de datos
            try {
                \DB::connection()->getPdo();
                Log::info('Conexión a base de datos exitosa');
            } catch (\Exception $e) {
                Log::error('Error de conexión a base de datos: ' . $e->getMessage());
                throw new \Exception('Error de conexión a base de datos');
            }
            
            $order = Order::create($orderData);
            
            if (!$order) {
                Log::error('No se pudo crear la orden - Order::create retornó null');
                throw new \Exception('No se pudo crear la orden en la base de datos');
            }
            
            Log::info('Orden creada exitosamente', ['order' => $order->toArray()]);
            
            return $order;
        } catch (\Exception $e) {
            Log::error('Error en createOrder', [
                'message' => $e->getMessage(),
                'data' => $orderData ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error al crear la orden: ' . $e->getMessage());
        }
    }

    private function processPayment(Request $request, Order $order)
    {
        try {
            $paymentServiceUrl = rtrim(env('PAYMENT_SERVICE_URL', 'http://localhost:8005'));
            
            $paymentData = [
                'order_id' => (string)$order->id,
                'status' => 'pendiente',
                'payment_method' => $request->payment_method,
                'amount' => (float)$request->total,
                'currency' => $request->currency
            ];
            
            Log::info('Intentando procesar pago', [
                'url' => $paymentServiceUrl . '/api/payments',
                'data' => $paymentData
            ]);

            $paymentResponse = Http::withHeaders([
                'Authorization' => $request->header('Authorization'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($paymentServiceUrl . '/api/payments', $paymentData);

            Log::info('Respuesta del servicio de pagos', [
                'status' => $paymentResponse->status(),
                'body' => $paymentResponse->body()
            ]);

            if (!$paymentResponse->successful()) {
                throw new \Exception('Error al crear el pago. Status: ' . $paymentResponse->status() . ' Body: ' . $paymentResponse->body());
            }

            return $paymentResponse->json();
        } catch (\Exception $e) {
            $order->delete();
            Log::error('Error en processPayment', [
                'message' => $e->getMessage(),
                'order_id' => $order->id
            ]);
            throw new \Exception('Error al procesar el pago: ' . $e->getMessage());
        }
    }

    private function updateOrderWithPayment(Order $order, array $payment)
    {
        $order->payment_id = $payment['id'];
        $order->save();
        $order->payment = $payment;
    }

    private function successResponse(Order $order)
    {
        return response()->json([
            'message' => 'Pedido creado exitosamente',
            'order' => $order
        ], 201);
    }

    private function errorResponse(\Exception $e)
    {
        Log::error('Error al crear pedido: ' . $e->getMessage());
        return response()->json([
            'error' => 'Error al crear pedido',
            'details' => $e->getMessage()
        ], 500);
    }

    /**
     * Muestra un pedido específico.
     */
    public function show(string $id)
    {
        try {
            $pedido = Order::with('user')->findOrFail($id);
            return response()->json($pedido);
        } catch (\Exception $e) {
            Log::error('Error al obtener pedido: ' . $e->getMessage());
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }
    }

    /**
     * Actualiza un pedido específico en la base de datos.
     */
    public function update(Request $request, string $id)
    {
        try {
            $pedido = Order::findOrFail($id);
            
            $datosValidados = $request->validate([
                'status' => 'sometimes|string',
                'total' => 'sometimes|numeric|min:0',
            ]);

            $pedido->update($datosValidados);

            return response()->json($pedido);
        } catch (\Exception $e) {
            Log::error('Error al actualizar pedido: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar pedido'], 500);
        }
    }

    /**
     * Elimina un pedido específico de la base de datos.
     */
    public function destroy(string $id)
    {
        try {
            $pedido = Order::findOrFail($id);
            $pedido->delete();
            return response()->json(['mensaje' => 'Pedido eliminado con éxito']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar pedido: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar pedido'], 500);
        }
    }
}
