<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Classement - NaviWaze</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand">🏆 Classement NaviWaze</span>
    <div>
        <a href="/map" class="btn btn-primary btn-sm">🔙 Carte</a>
        <a href="/logout" class="btn btn-danger btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container mt-4">

    <h3 class="text-center mb-4">Top 10 des meilleurs contributeurs</h3>

    <table class="table table-bordered table-striped text-center">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Utilisateur</th>
                <th>Signalements</th>
                <th>Votes 👍 reçus</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $i => $user)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->events_count }}</td>
                    <td>{{ $user->up_votes_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

</body>
</html>
