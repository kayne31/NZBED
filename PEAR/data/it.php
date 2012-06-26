<?php
$errorCodes = array();
$errorCodes['it'] = array(
    'TOO_LARGE'             => "Il file &eacute; troppo grande. Il massimo permesso &eacute: $maxsize bytes.",
    'MISSING_DIR'           => 'Manca la directory di destinazione.',
    'IS_NOT_DIR'            => 'La directory di destinazione non esiste o &eacute; un file.',
    'NO_WRITE_PERMS'        => 'Non si hanno i permessi di scrittura sulla directory di destinazione.',
    'NO_USER_FILE'          => 'Nessun file selezionato per l\'upload.',
    'BAD_FORM'              => 'Il modulo HTML non contiene gli attributi richiesti: "'.
           ' method="post" enctype="multipart/form-data".',
    'E_FAIL_COPY'           => 'Copia del file temporaneo fallita.',
    'E_FAIL_MOVE'           => null,
    'FILE_EXISTS'           => 'File destinazione gi&agrave; esistente.',
    'CANNOT_OVERWRITE'      => 'File destinazione gi&agrave; esistente e non si pu&ograve; sovrascrivere.',
    'NOT_ALLOWED_EXTENSION' => 'Estensione del File non permessa.',
    'PARTIAL'               => null,
    'ERROR'                 => null,
    'DEV_NO_DEF_FILE'       => null,
);
?>
