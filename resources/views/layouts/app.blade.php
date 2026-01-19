<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title', 'NavApp')</title>

  <!-- Bootstrap CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <style>
    #map { height: 70vh; }
  </style>

  @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <a class="navbar-brand" href="{{ route('map') }}">NavApp</a>
    <div>
      @if(session()->has('user_id'))
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
          @csrf
          <button class="btn btn-outline-secondary btn-sm">Déconnexion</button>
        </form>
      @else
        <a href="{{ route('login.form') }}" class="btn btn-primary btn-sm">Connexion</a>
        <a href="{{ route('register.form') }}" class="btn btn-outline-primary btn-sm">Inscription</a>
      @endif
    </div>
  </div>
</nav>

<div class="container my-4">
  @yield('content')
</div>

<!-- Bootstrap JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Axios for AJAX -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

@stack('scripts')
</body>
</html>
