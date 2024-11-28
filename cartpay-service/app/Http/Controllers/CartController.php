<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index(Request $request)
    {
        try {
            $cart = Cart::where('user_id', $request->user()->id)
                       ->where('estado', 'pendiente')
                       ->get();
            return response()->json($cart);
        } catch (\Exception $e) {
            Log::error('Error al obtener carrito: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el carrito'], 500);
        }
    }

    public function addToCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'cantidad' => 'required|integer|min:1',
            ]);

            // Verificar stock del producto mediante API del servicio de productos
            $product = Http::get(env('PRODUCTS_SERVICE_URL') . '/products/' . $validated['product_id']);
            
            if (!$product->successful()) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            $productData = $product->json();
            
            if ($productData['stock'] < $validated['cantidad']) {
                return response()->json(['error' => 'Stock insuficiente'], 400);
            }

            $cart = Cart::create([
                'user_id' => $request->user()->id,
                'product_id' => $validated['product_id'],
                'cantidad' => $validated['cantidad'],
                'precio_unitario' => $productData['precio'],
                'precio_total' => $productData['precio'] * $validated['cantidad'],
                'estado' => 'pendiente'
            ]);

            return response()->json($cart, 201);
        } catch (\Exception $e) {
            Log::error('Error al agregar al carrito: ' . $e->getMessage());
            return response()->json(['error' => 'Error al agregar al carrito'], 500);
        }
    }

    public function updateCart(Request $request, $id)
    {
        try {
            $cart = Cart::where('id', $id)
                       ->where('user_id', $request->user()->id)
                       ->first();

            if (!$cart) {
                return response()->json(['error' => 'Carrito no encontrado'], 404);
            }

            $validated = $request->validate([
                'cantidad' => 'required|integer|min:1'
            ]);

            $cart->cantidad = $validated['cantidad'];
            $cart->precio_total = $cart->precio_unitario * $validated['cantidad'];
            $cart->save();

            return response()->json($cart);
        } catch (\Exception $e) {
            Log::error('Error al actualizar carrito: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el carrito'], 500);
        }
    }

    public function checkout(Request $request)
    {
        try {
            $cart = Cart::where('user_id', $request->user()->id)
                       ->where('estado', 'pendiente')
                       ->get();

            foreach ($cart as $item) {
                $item->estado = 'pagado';
                $item->save();

                // Actualizar stock del producto
                $response = Http::get(env('PRODUCTS_SERVICE_URL') . '/products/' . $item->product_id);
                $product = $response->json();
                
                Http::put(env('PRODUCTS_SERVICE_URL') . '/products/' . $item->product_id, [
                    'stock' => $product['stock'] - $item->cantidad
                ]);
            }

            return response()->json(['message' => 'Compra realizada con éxito']);
        } catch (\Exception $e) {
            Log::error('Error en checkout: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar la compra'], 500);
        }
    }
}