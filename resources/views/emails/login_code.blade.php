<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Code de vérification</title>
</head>
<body>
    <p>Bonjour {{ $name ?? 'utilisateur' }},</p>
    <p>Voici votre code de vérification :</p>
    <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">{{ $code }}</p>
    <p>Ce code expire dans 10 minutes. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.</p>
</body>
</html>
