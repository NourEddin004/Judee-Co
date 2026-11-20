{{-- Every error page comes through here. Store pages for 4xx errors use the store's layout; server
     errors, maintenance and the admin use the stand-alone page, which needs no database. If the store
     layout itself fails, the stand-alone page is shown instead of a raw error. --}}
@php
    $errorPage = \App\Support\ErrorPage::for((int) $status, request());
    $errorHtml = null;
    if (! $errorPage['admin'] && $errorPage['status'] < 500) {
        try {
            $errorHtml = view('errors.store', ['errorPage' => $errorPage])->render();
        } catch (\Throwable $e) {
            report($e);
            $errorHtml = null;
        }
    }
@endphp
{!! $errorHtml ?? view('errors.shell', ['errorPage' => $errorPage])->render() !!}
