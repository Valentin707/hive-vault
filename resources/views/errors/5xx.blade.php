@include('errors.page', [
    'code' => $exception->getStatusCode(),
    'category' => 'Service fault',
    'title' => 'The node could not complete that',
    'message' => 'An unexpected service fault interrupted this request. Please return and try again.',
    'actionUrl' => route('dashboard'),
    'actionLabel' => 'Return to dashboard',
])
