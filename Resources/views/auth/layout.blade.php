<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="robots" content="noindex, nofollow">

    @include('devel::seo._metatags')

    <link rel="stylesheet" href="{{ mix('css/devel/dashboard.css') }}">
    @stack('styles')
</head>
<body>
    <div id="vue" class="container">
        <main class="main">
            <section class="content center">
                @yield('content')
            </section>
        </main>
    </div>

    <script src="{{ mix('js/devel/dashboard.js') }}"></script>
    @stack('scripts')
</body>
</html>