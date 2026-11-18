@include('errors.page', [
    'code' => '503',
    'category' => 'Service unavailable',
    'title' => 'Vault is temporarily offline',
    'message' => 'This node is temporarily unavailable, possibly during maintenance. Please try again shortly.',
    'actionUrl' => route('dashboard'),
    'actionLabel' => 'Try the dashboard',
])
