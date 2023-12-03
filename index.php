<?php
// Include router class
include('util/Conexion.php');
include('util/Router.php');
//var_dump($_SERVER);
if(!isset($_SERVER["HTTP_REFERER"])){
    #Si no está definida, quiere decir que aún no se ha cargado el contenido principal
    require_once 'vistas/home.html';
    die();
}

// Las rutas bases
Router::add('/', function(){
    require_once 'vistas/home.html';
});
Router::add('/index.php', function(){
    require_once 'vistas/home.html';
});
Router::add('/home.html', function(){
    require_once 'vistas/home.html';
});

//CSS
Router::add('/css/([a-zA-Z]+\.css)', function($var){
    require_once "css/$var";
});

Router::add('/ver-usuarios',function(){
    require 'vistas/verUsuarios.php';
});

Router::add('/ver-usuario/([0-9]*)',function($var1){
    echo "Se mostrará el usuario con el ID $var1";
}, "get");

Router::add('/crear-usuario.html',function(){
    require 'vistas/crearUsuario.html';
},'get');

// POST rutas
Router::add('/acciones/crear-usuario',function(){
    require 'post/crearUsuario.php';
},'post');


Router::run('/');
