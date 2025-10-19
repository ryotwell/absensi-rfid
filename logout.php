<?php

require_once 'bootstrap.php';

session_start();

unset($_SESSION['user']);

return redirectTo('login.php');