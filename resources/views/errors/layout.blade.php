{{-- Laravel's own error pages (401, 402, 413 ...) extend this layout: they get the branded page too. --}}
@include('errors.render', ['status' => isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500])
