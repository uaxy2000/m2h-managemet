<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — M2H Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    @yield('content')
</body>
</html>
