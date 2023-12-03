<?php

/**
 * Description of Conexion
 *
 * @author noeas
 */
class Conexion {
    private $database = "usuarios";
    private $username = "root";
    private $password = "password";
    private $host = "db";
    private $puerto = 3306;
    private $conexion;
    private static $instancia;

    /**
     * 
     * @return Conexion
     */
    public static function obtenerInstancia() {
        if (!self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        $conexion = new mysqli($this->host, $this->username, $this->password, $this->database, $this->puerto);
        //$conexion = new mysqli($this->host, $this->username, $this->password, $this->database);
        $conexion->set_charset("utf8mb4");
        $conexion->query("SET sql_safe_updates=1"); //No permite modificar ni eliminar más de un elemento por sentencia
        $conexion->query("SET SQL_MODE=''"); // El group by viene por defecto de manera estricta y no funciona en muchas ocaciones
        $this->conexion = $conexion;
    }

    private function __clone() {
        //Para evitar la clonación de nuestra instancia
    }
    /**
     * Obtiene la instancia a la conexion directamente
     * @return mysqli
     */
    public function obtenerConexion(){
        return $this->conexion;
    }
    public function delete($sentencia, $tipoDatos, $datos, $convertirAJSON = true) {
        if (!$this->starts_with_ignore_case($sentencia, "delete")) {
            return '{"exito":"no","motivo":"La sentencia que estas tratando de usar no es una eliminación."}';
        }
        $sentencia_eliminar = $this->conexion->prepare($sentencia);
        if ($sentencia_eliminar) {
            $sentencia_eliminar->bind_param($tipoDatos, ...$datos);
            $sentencia_eliminar->execute();
            if ($sentencia_eliminar->affected_rows === 1) {
                $sentencia_eliminar->close();
                $retorno = ["exito" => "si", "mensaje" => "Se ha eliminado el elemento."];
            } else if ($sentencia_eliminar->affected_rows === 0) {
                $retorno = ["exito" => "no", "motivo" => "El elemento a eliminar, no existe."];
            } else {
                $error = $sentencia_eliminar->error;
                $sentencia_eliminar->close();
                $retorno = ["exito" => "no", "motivo" => $error];
            }
        } else {
            $retorno = ["exito" => "no", "motivo" => "Error con la sentencia: $sentencia_eliminar."];
        }
        if ($convertirAJSON === true) {
            $retorno = json_encode($retorno);
        }
        return $retorno;
    }

    public function insert($sentencia, $tipoDatos, $datos, $convertirAJSON = true) {
        if (!$this->starts_with_ignore_case($sentencia, "insert")) {
            return '{"exito":"no","motivo":"La sentencia que estas tratando de usar no es una inserción."}';
        }
        $conexion = $this->conexion;
        $sentencia_insert = $conexion->prepare($sentencia);
        if ($sentencia_insert === false) {
            $error = $conexion->error;
            $retorno = ["exito" => "no", "motivo" => "Algún error con la sentencia: $error."];
            if ($convertirAJSON === true) {
                return json_encode($retorno);
            }
            return $retorno;
        }
        $sentencia_insert->bind_param($tipoDatos, ...$datos);
        $sentencia_insert->execute();
        if ($sentencia_insert->affected_rows > 0) {
            $idGenerado = $sentencia_insert->insert_id;
            $sentencia_insert->free_result();
            $sentencia_insert->close();
            $retorno = ["exito" => "si", "mensaje" => "Se ha insertado el elemento", "idGenerado" => $idGenerado];
            if ($convertirAJSON === true) {
                return json_encode($retorno);
            }
            return $retorno;
        } else {
            $error = $conexion->error;
            $retorno = ["exito" => "no", "motivo" => "Algún error tratando de insertar: $error."];
            $sentencia_insert->close();
            if ($convertirAJSON === true) {
                return json_encode($retorno);
            }
            return $retorno;
        }
    }

    /**
     * Efectua una consulta a la base de datos
     * @param String $sentencia - sentencia que debe comenzar con la frase "select"
     * @param String $tipoDatos - String que indica en orden el tipo de datos que se debe ir en cada "?" de la sentencia,
     * teniendo en cuenta que i => es para enteros, d => numeros con o sin decimal (float, double, decimal), 
     * s => es para cadenas (strings), fechas, JSON o las que apliquen, solo existen esos tres tipos de indicadores "i", "d" y "s", 
     * cualquier otra letra devolerá un error, en caso de no tener "?" en la sentencia, enviar cadena vacia ""
     * @param Array $datos - Arreglo sencillo con los datos a remplazar los "?" en la sentencia en orden de aparicion en esta, 
     * en caso de no tener "?" en la sentencia, enviar arreglo vacio []
     * @param boolean $convertirAJSON - Bandera que indica si el resultado sera devuelto o no en JSON, por defecto es true, enviar false para devolver los datos
     * en un arreglo de PHP
     * @return JSON|array - por defecto retorna JSON, retorna un array si el parametro opcional $convertir a JSON 
     * es igual a false
     */
    public function select(string $sentencia, string $tipoDatos, array $datos, bool $convertirAJSON = true, mixed $hola = null) {
        try {
            if (!$this->starts_with_ignore_case($sentencia, "select") && !$this->starts_with_ignore_case($sentencia, "with")) {
                return '{"exito":"no","motivo":"La sentencia que estas tratando de usar no es una selección."}';
            }

            $conexion = $this->conexion;
            $sentencia_select = $conexion->prepare($sentencia);
            if ($sentencia_select === false) {
                $error = $conexion->error;
                $retorno = ["exito" => "no", "motivo" => "Error en la consulta: \"$error\", tu sentencia es: \"$sentencia\""];
                if ($convertirAJSON) {
                    return json_encode($retorno);
                }
                return $retorno;
            }
            if (count($datos) > 0) {
                $sentencia_select->bind_param($tipoDatos, ...$datos);
            }
            $sentencia_select->execute();
            /* if($hola = 10){
              var_dump($sentencia_select);
              } */
            $i = 0;
            $resultados = $sentencia_select->get_result();
            $registros = [];
            if ($resultados === false) {
                if ($convertirAJSON) {
                    return '{"exito":"no", "motivo":"Un error con la sentencia, código de error: ' . $conexion->errno . '"}';
                }
                return ["exito" => "no", "motivo" => "Un error con la sentencia, código de error: $conexion->errno"];
            }
            while ($temporal = $resultados->fetch_assoc()) {
                $registros[$i] = $temporal;
                $i++;
            }
            $registrosCodificados = $registros;
            //$registrosCodificados = $this->recorrer($registros);
            $retorno = ["exito" => "si", "consulta" => $sentencia, "seleccion" => $registrosCodificados];
            if ($convertirAJSON === true) {
                $retorno = json_encode($retorno);
            }
            //'{"exito":"si", "consulta":"' . $sentencia . '","seleccion":' . json_encode($registrosCodificados) . '}';
            $sentencia_select->free_result();
            $sentencia_select->close();
            return $retorno;
        } catch (mysqli_sql_exception $mysqliex) {
            $mensaje = $mysqliex->getMessage();
            $retorno = ["exito" => "no", "motivo" => $mensaje];
            if ($convertirAJSON === true) {
                $retorno = json_encode($retorno);
            }
            return $retorno;
        } catch (Exception $ex) {
            #Es mejor imprimir los errores a tratar de capturarlos
            $mensaje = $ex->getMessage();
            $retorno = ["exito" => "no", "motivo" => "Error en máquina virtual: $mensaje"];
            if ($convertirAJSON === true) {
                $retorno = json_encode($retorno);
            }
            return $retorno;
        }
    }

    public function update($sentencia, $tipoDatos, $datos, $convertirAJSON = true) {
        try {
            if (!$this->starts_with_ignore_case($sentencia, "update")) {
                return '{"exito":"no","motivo":"La sentencia que estas tratando de usar no es una modificación."}';
            }
            $conexion = $this->conexion;
            $sentencia_update = $conexion->prepare($sentencia);
            if ($sentencia_update === false) {
                $respuestaSQL = $conexion->error;
                return '{"exito":"no", "motivo":"Error con la sentencia: ' . $sentencia . ', el motor SQL dice: ' . $respuestaSQL . '"}';
            }
            $sentencia_update->bind_param($tipoDatos, ...$datos);
            $sentencia_update->execute();
            $afectados = $sentencia_update->affected_rows;
            $prototype = $conexion->info;
            list($matched, $changed, $warnings) = sscanf($prototype, "Rows matched: %d Changed: %d Warnings: %d");
            if ($afectados > 0 || $matched > 0) {//porque si no hay cambios afectados queda en 0 pero matched si aumenta de 0
                $retorno = [
                    "exito" => "si", "mensaje" => "Se ha modificado todo correctamente", "afectados" => $afectados, "alcanzados" => $matched
                ];
            } elseif ($matched === 0 && $changed === 0 && $warnings === 0) {
                $retorno = [
                    "exito" => "advertencia", "motivo" => "El elemento a modificar, no existe."
                ];
            } else {
                $error = $sentencia_update->error;
                $retorno = [
                    "exito" => "no", "motivo" => "Algún error: $error"
                ];
            }
            $sentencia_update->close();
            if($convertirAJSON){
                return json_encode($retorno);
            }
            return $retorno;
        } catch (Exception $ex) {
            $retorno = ["exito"=>"no", "motivo"=>"El sistema lanzo una excepción: $ex"];
            if($convertirAJSON){
                return json_encode($retorno);
            }
            return $retorno;            
        }
    }

    public function cerrarConexion() {
        $this->conexion->close();
    }

    private function starts_with_ignore_case($haystack, $needle) {
        $haystack_uc = strtoupper($haystack);
        $needle_uc = strtoupper($needle);
        return (string) $needle_uc !== '' && strncmp($haystack_uc, $needle_uc, strlen($needle_uc)) === 0;
    }

}
