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
     * @return void
     */
    public function render($request)
    {
        return new JsonResponse([
            'errors' => [
                'message' => $this->getMessage()
            ],
        ], $this->code);
    }
}
