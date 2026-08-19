<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();
Auth::logout();
header('Location: index.php');
exit;
