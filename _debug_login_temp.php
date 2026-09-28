<?php
session_start();
$_SESSION['usuario_id'] = 9;
$_SESSION['usuario_rol'] = 'administrador';
echo session_id();
