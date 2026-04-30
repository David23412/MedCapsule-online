<div class="live-users"
     data-reverb-key="{{ env('REVERB_APP_KEY') }}"
     data-reverb-host="{{ env('REVERB_HOST') }}"
     data-reverb-port="{{ env('REVERB_PORT') }}"
     data-reverb-scheme="{{ env('REVERB_SCHEME') }}"
     data-count-endpoint="{{ url('/api/active-users') }}">
    <div class="live-indicator">
        <div class="live-dot"></div>
        <div class="ripple"></div>
    </div>
    <div class="counter-content">
        <span class="user-count" data-count="{{ $count }}">{{ $count }}</span>
        <span class="user-text">active now</span>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/live-user-counter.css') }}">
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0-rc2/dist/web/pusher.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js" defer></script>
<script src="{{ asset('js/live-user-counter.js') }}" defer></script>
