@include('errors.page', [
    'code' => '500',
    'category' => 'Internal fault',
    'title' => 'The node hit an error',
    'message' => 'The request could not be completed. Return to the dashboard and try again.',
    'actionUrl' => route('dashboard'),
    'actionLabel' => 'Return to dashboard',
])
