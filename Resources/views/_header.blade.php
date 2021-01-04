<header class="header">
    <div class="left"></div>

    <div class="right">
        {{ Auth::user()->name }}
        ({{ Auth::user()->roles->pluck('name')->join(', ') }})
        |
        <a href="#" onclick="document.getElementById('logout').submit()">Logout</a>

        <form id="logout"
            action="{{ route('dashboard.auth.logout') }}"
            method="POST">@csrf</form>
    </div>
</header>