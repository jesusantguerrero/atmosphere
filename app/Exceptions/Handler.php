<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

    }

    /**
     * A stale CSRF token — a tab left open past the session lifetime — used to dump
     * the user on Laravel's raw "Page Expired" screen, losing whatever they had
     * typed. Send them back to the page they came from (that render carries a fresh
     * token) with the app banner explaining why nothing was saved.
     *
     * The check lives here rather than in a `renderable` callback because Laravel
     * maps TokenMismatchException to a 419 HttpException before those callbacks run.
     */
    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);

        if ($response->getStatusCode() === 419 && ! $this->shouldReturnJson($request, $e)) {
            return back()->with('flash', [
                'banner' => __('Your session expired before that could be saved. The page is fresh again — please try once more.'),
                'bannerStyle' => 'danger',
            ]);
        }

        return $response;
    }

    /**
     * A logged-out user used to land on a bare /login with no idea what happened —
     * the reported "logout a media sesión" that ate a half-filled transaction. The
     * session can end mid-work for two reasons: it simply expired, or Jetstream's
     * AuthenticateSession middleware force-closed it because the password hash in
     * the session no longer matches the user's (a password change, or Laravel 11
     * rehashing the password on another login). Either way, say so on the login
     * screen instead of bouncing them silently.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->shouldReturnJson($request, $exception)) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        return redirect()
            ->guest($exception->redirectTo($request) ?? route('login'))
            ->with('status', __('Your session ended, so we signed you out. Sign in again to pick up where you left off.'));
    }
}
