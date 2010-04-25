<?php
define('TWEET_COUNT', 100); // número de tweets que se cargarán cada vez. cuanto menor sea el número, menos tardará en cargar, pero las posibilidades de pasar un tweet aprovechable son mayores

define('AUTHUSER', 'tuusuario'); // usuario que usará en caso de que $datasource tenga los updates protegidos
define('AUTHPWD', 'tucontraseña'); // contraseña ídem

mysql_connect('localhost', 'usuario', 'contraseña'); // conexión a la base de datos
mysql_select_db('base_de_datos'); 