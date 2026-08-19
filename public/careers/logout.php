<?php
require __DIR__ . '/../../config/bootstrap.php';
Session::start('ultrademy_careers_session');
Auth::logout();
header('Location: app.php');
exit;
