<?php
$Conexion = Conexion::obtenerInstancia();
$nombre = $_POST["nombre"];
$apellidos = $_POST["apellidos"];
$edad = $_POST["edad"];
$genero = $_POST["genero"];

$sentencia = "INSERT INTO `usuarios`(`nombre`, `apellidos`, `genero`, `edad`) VALUES (?,?,?,?) ";
$verificarConsulta = $Conexion->insert($sentencia, "sssi", [$nombre, $apellidos, $genero, $edad], false);
if($verificarConsulta["exito"] === "no"){
    die("Ha ocurrido algún error");
} else {
    $idGenerado = $verificarConsulta["idGenerado"];
    echo "Felicidades, el usuario $nombre ha sido creado con el ID: $idGenerado"
            . "<script>setTimeout(function(){window.location.href = '../';}, 2000);</script>";
}