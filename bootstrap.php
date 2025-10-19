<?php

require_once 'database.php';

function redirectTo(string $url)
{
    return header("Location: $url");
}
