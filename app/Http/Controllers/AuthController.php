<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use OpenApi\Annotations as OA;
use Sentry\SentrySdk;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="API Carteira Financeira",
 * description="Documentação da API da Carteira Financeira para autenticação e gerenciamento de usuários."
 * )
 * @OA\Components(
 * @OA\SecurityScheme(
 * securityScheme="sanctum",
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT",
 * description="Autenticação via Sanctum. Insira o token Bearer."
 * )
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/register",
     * operationId="registerUser",
     * tags={"Autenticação"},
     * summary="Registra um novo usuário",
     * description="Cria um novo usuário e sua respectiva carteira com saldo inicial zero.",
     * @OA\RequestBody(
     * required=true,
     * description="Dados para registro do novo usuário",
     * @OA\JsonContent(
     * required={"name", "email", "password", "password_confirmation"},
     * @OA\Property(property="name", type="string", example="José da Silva"),
     * @OA\Property(property="email", type="string", format="email", example="jose.silva@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Usuário criado com sucesso",
     * @OA\JsonContent(ref="#/components/schemas/User")
     * ),
     * @OA\Response(
     * response=422,
     * description="Erro de validação (ex: email já existe, senha fraca)"
     * )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                $user->wallet()->create(['balance' => 0]);

                return $user;
            });

            return response()->json($user, 201);
        } catch (\Throwable $e) {
            Sentry::captureException($e);
            return response()->json(['message' => 'Ocorreu um erro inesperado durante o registro.'], 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/login",
     * operationId="loginUser",
     * tags={"Autenticação"},
     * summary="Autentica um usuário",
     * description="Autentica o usuário com email e senha e retorna um token de API.",
     * @OA\RequestBody(
     * required=true,
     * description="Credenciais para autenticação",
     * @OA\JsonContent(
     * required={"email", "password"},
     * @OA\Property(property="email", type="string", format="email", example="jose.silva@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Login bem-sucedido",
     * @OA\JsonContent(
     * @OA\Property(property="token", type="string", example="1|aBcDeFgHiJkLmNoPqRsTuVwXyZ"),
     * @OA\Property(property="user", ref="#/components/schemas/User")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Credenciais inválidas"
     * )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            Sentry::captureMessage("Tentativa de login falhou para o email: {$request->email}", 'warning');

            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * operationId="logoutUser",
     * tags={"Autenticação"},
     * summary="Desloga o usuário",
     * description="Invalida o token de API do usuário autenticado.",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Logout bem-sucedido",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Deslogado com sucesso")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Não autenticado"
     * )
     * )
     */
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return response()->json(['message' => 'Deslogado com sucesso']);
    }
}