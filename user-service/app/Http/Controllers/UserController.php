<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    public function verifyToken(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $request->header('Authorization')
            ])->get(env('USER_SERVICE_URL') . '/api/user');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Token inválido'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error de autenticación'], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'rol' => 'required|string|in:admin,user'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol
        ]);

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,'.$id,
            'password' => 'string|min:8',
            'rol' => 'string|in:admin,user'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if ($request->has('nombre')) $user->nombre = $request->nombre;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        if ($request->has('rol')) $user->rol = $request->rol;

        $user->save();
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error en el inicio de sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'rol' => 'required|string|in:admin,user'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = User::create([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'rol' => $request->rol
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Usuario registrado exitosamente',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error en registro: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user (Revoke the token)
     */
    public function logout(Request $request)
    {
        try {
            if (!$request->user()) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }
            
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Sesión cerrada correctamente']);
        } catch (\Exception $e) {
            \Log::error('Error en logout: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
