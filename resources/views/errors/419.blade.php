{{-- Fallback: should never be seen — the exception handler redirects before this renders.
     If it somehow renders, redirect instantly via meta-refresh + JS. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="0;url=/">
    <title>Redirecting…</title>
    <script>window.location.replace('/');</script>
</head>
<body></body>
</html>
