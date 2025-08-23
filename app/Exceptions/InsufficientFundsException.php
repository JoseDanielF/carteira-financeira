<?php

namespace App\Exceptions;

use Exception;

class InsufficientFundsException extends Exception
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'error' => 'Saldo insuficiente.',
            'message' => $this->getMessage(),
        ], 400);
    }
}
