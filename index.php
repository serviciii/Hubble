<?php
require_once __DIR__ . '/config/helpers.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

redirect('auth/masuk.php');
