<?php
declare(strict_types=1);

/**
 * Ultrademy — front controller.
 * Every request enters here. Routing comes next.
 */

require __DIR__ . '/../config/bootstrap.php';

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(config('app.name')) ?></title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <main class="shell">
        <h1><?= htmlspecialchars(config('app.name')) ?></h1>
        <p class="muted">Scaffold is up. PHP <?= PHP_VERSION ?>.</p>
    </main>
</body>
</html>
