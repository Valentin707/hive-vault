@include('errors.page', [
    'code' => '429',
    'category' => 'Rate limited',
    'title' => 'Slow down a moment',
    'message' => 'Too many requests reached this node. Wait briefly, then try again.',
    'actionUrl' => route('dashboard'),
    'actionLabel' => 'Return to dashboard',
])
