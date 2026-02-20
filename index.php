<?php
<<<<<<< HEAD
/** Главная страница сайта */
require_once __DIR__ . '/includes/init.php';
header('Location: /dashboard.php');
exit;
=======
require_once __DIR__ . '/includes/init.php';
if (!isLoggedIn()) {
    require_once __DIR__ . '/pages/login.php';
    exit;
}
header('Location: /dashboard.php');
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
