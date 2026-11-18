@include('errors.page', [
    'code' => '401',
    'category' => 'Authentication required',
    'title' => 'Identity signal required',
    'message' => 'Connect through Hive Identity before continuing to this node.',
    'actionUrl' => route('login'),
    'actionLabel' => 'Connect to Hive Identity',
])
