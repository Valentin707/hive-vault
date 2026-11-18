@include('errors.page', [
    'code' => '403',
    'category' => 'Access control',
    'title' => 'Access denied',
    'message' => 'This account does not have permission to access the requested resource on this node.',
    'actionUrl' => route('dashboard'),
    'actionLabel' => 'Return to dashboard',
])
