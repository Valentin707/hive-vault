@include('errors.page', [
    'code' => $exception->getStatusCode(),
    'category' => 'Request rejected',
    'title' => 'Request could not be completed',
    'message' => 'The request could not be completed. Check the address or your access, then try again.',
    'actionUrl' => route('dashboard'),
    'actionLabel' => 'Return to dashboard',
])
