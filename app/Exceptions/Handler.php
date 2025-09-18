<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($e instanceof ValidationException) {
                $json = [
                    'error' => $e->validator->errors(),
                    'status_code' => 500
                ];
            } elseif ($e instanceof AuthorizationException) {
                $json = [
                    'error' => 'You are not allowed to do this action.',
                    'status_code' => 403
                ];
            } elseif ($e instanceof NotFoundHttpException) {
                $json = [
                    'error' => 'Sorry, the page you are looking for could not be found.',
                    'status_code' => 403,
                ];
            } else {
                $json = [
                    'error' => (app()->environment() !== 'production')
                        ? $e->getMessage()
                        : 'An error has occurred.',
                    'status_code' => 500
                ];
            }
            return response()->json($json, 500);
        });
    }
}
