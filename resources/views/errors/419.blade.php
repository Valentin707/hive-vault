@include('errors.page', [
    'code' => '419',
    'category' => 'Session expired',
    'title' => 'Session timed out',
    'message' => 'This request expired. Reconnect to Hive Identity to refresh your local session.',
    'actionUrl' => route('login'),
    'actionLabel' => 'Reconnect to Hive',
])
