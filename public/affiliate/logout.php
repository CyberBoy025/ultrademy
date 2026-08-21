<?php
require __DIR__ . '/../../config/bootstrap.php';
Session::start('ultrademy_affiliate_session');
Auth::logout();
header('Location: login.php');
exit;
