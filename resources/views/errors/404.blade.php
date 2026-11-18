@include('errors.page', [
    'code' => '404',
    'category' => 'Routing error',
    'title' => 'Signal not found',
    'message' => 'That path does not map to a resource on this satellite node.',
    'actionUrl' => route('dashboard'),
    'actionLabel' => 'Return to dashboard',
])
