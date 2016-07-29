<?php

namespace BookStack\Exceptions;

use Exception;
use Illuminate\Contracts\Validation\ValidationException;
use PhpSpec\Exception\Example\ErrorException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

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
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $e
     */
    public function report(Exception $e)
    {
        return parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        // Handle notify exceptions which will redirect to the
        // specified location then show a notification message.
        if ($e instanceof NotifyException) {
            \Session::flash('error', $e->message);
            return response()->redirectTo($e->redirectLocation);
        }

        // Handle pretty exceptions which will show a friendly application-fitting page
        // Which will include the basic message to point the user roughly to the cause.
        if (($e instanceof PrettyException || $e->getPrevious() instanceof PrettyException)  && !config('app.debug')) {
            $message = ($e instanceof PrettyException) ? $e->getMessage() : $e->getPrevious()->getMessage();
            $code = ($e->getCode() === 0) ? 500 : $e->getCode();
            return response()->view('errors/' . $code, ['message' => $message], $code);
        }

        return parent::render($request, $e);
    }
    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $e
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $e)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        } else {
            return redirect()->guest('login');
        }
    }
}
