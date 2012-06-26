<?php
$errorCodes = array();
$errorCodes['es'] = array(
    'TOO_LARGE'             => "Fichero demasiado largo. El maximo permitido es: $maxsize bytes.",
    'MISSING_DIR'           => 'Falta directorio destino.',
    'IS_NOT_DIR'            => 'El directorio destino no existe o es un fichero regular.',
    'NO_WRITE_PERMS'        => 'El directorio destino no tiene permisos de escritura.',
    'NO_USER_FILE'          => 'No se ha escogido fichero para el upload.',
    'BAD_FORM'              => 'El formulario no contiene method="post" enctype="multipart/form-data" requerido.',
    'E_FAIL_COPY'           => 'Fallo al copiar el fichero temporal.',
    'E_FAIL_MOVE'           => 'No puedo mover el fichero.',
    'FILE_EXISTS'           => 'El fichero destino ya existe.',
    'CANNOT_OVERWRITE'      => 'El fichero destino ya existe y no se puede sobreescribir.',
    'NOT_ALLOWED_EXTENSION' => 'Extension de fichero no permitida.',
    'PARTIAL'               => 'El fichero fue parcialmente subido',
    'ERROR'                 => 'Error en subida:',
    'DEV_NO_DEF_FILE'       => 'No est&aacute; definido en el formulario este nombre de fichero como &lt;input type="file" name=?&gt;.',
);
?>
