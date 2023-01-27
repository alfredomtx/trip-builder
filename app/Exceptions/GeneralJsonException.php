<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeneralJsonException extends Exception
{
    /**
     * Report the exception
     *
     * @return void
     */
    public function report()
    {

    }

    /**
     * Render the exception as an HTTP Response
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function render($request)
    {
        return new JsonResponse([
                'message' => $this->getMessage()
        ], $this->code);
    }
}
