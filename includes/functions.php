<?php
// includes/functions.php

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}