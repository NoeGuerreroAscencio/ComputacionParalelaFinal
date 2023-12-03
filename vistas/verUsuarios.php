<?php
$Conexion = Conexion::obtenerInstancia();
$verificarConsulta = $Conexion->select("SELECT * FROM usuarios order by id_usuario", "", [], false);
$html = "";
foreach ($verificarConsulta["seleccion"] as $usuario) {
    $html .= '<tr class="fila-elemento">'
            . '<td>' . $usuario["id_usuario"] . '</td>'
            . '<td>' . $usuario["nombre"] . '</td>'
            . '<td>' . $usuario["apellidos"] . '</td>'
            . '<td>' . $usuario["genero"] . '</td>'
            . '<td>' . $usuario["edad"] . '</td>'
            . '</tr>';
}
?>
<div class="barra">
    <h2 class="textoBarra">Ver usuarios</h2>
</div>
<div class="centrado"> 
    <table class="tabla tabla-ver-usuarios">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellidos</th>
            <th>Género</th>
            <th>Edad</th>
        </tr>
        <?php echo $html;?>
    </table>
</div>
