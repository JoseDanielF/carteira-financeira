<?php

namespace App\Exceptions;

use Exception;

class TransactionReversalException extends Exception
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'error' => 'Falha no estorno.',
            'message' => $this->getMessage(),
        ], 400);
    }
}
