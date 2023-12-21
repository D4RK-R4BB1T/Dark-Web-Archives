<?php

namespace App\Exceptions;

use Exception;
use HttpResponseException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
        \Predis\Connection\ConnectionException::class
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        if ($exception instanceof \PDOException || $exception instanceof QueryException) {
            // occurs while bootstrapping
            if (starts_with($exception->getMessage(), 'SQLSTATE[HY000] [2002] Connection refused')) {
                return;
            }
        }
        parent::report($exception);
    }

    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        if ($e->response) {
            return $e->response;
        }

        $errors = $e->validator->errors()->getMessages();

        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }

        return redirect()->back(303)->withInput(
            $request->input()
        )->withErrors($errors);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $exception)
    {
        $e = $this->prepareException($exception);

        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        } elseif ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);
        }

        if ($this->isHttpException($e) && !config('app.debug')) {
            /** @var HttpException $exception */
            switch ($e->getStatusCode()) {
                case '403':
                    return response()->view('errors.403', ['hide_header' => true]);

                case '404':
                    return response()->view('errors.404', ['hide_header' => true]);

                case '500':
                    return response()->view('errors.500', ['hide_header' => true]);

                case '503':
                    return response()->view('errors.503', ['hide_header' => true]);

                default:
                    return response()->view('errors.default', ['exception' => $e, 'hide_header' => true]);
            }
        }

        if (!config('app.debug')) {
            return response()->view('errors.default', ['exception' => $e, 'hide_header' => true]);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('/auth/login', 303)->with('login_required', true);
    }
}
