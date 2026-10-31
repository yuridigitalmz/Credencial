<?php
require_once __DIR__ . '/inc/bootstrap.php';
registar('Sessão terminada');
session_unset(); session_destroy();
redirect('/index.php');
