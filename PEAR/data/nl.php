<?php
$errorCodes = array();
$errorCodes['nl'] = array(
    'TOO_LARGE'             => "Het bestand is te groot, de maximale grootte is: $maxsize bytes.",
    'MISSING_DIR'           => 'Geen bestemmings directory.',
    'IS_NOT_DIR'            => 'De doeldirectory bestaat niet, of is een gewoon bestand.',
    'NO_WRITE_PERMS'        => 'Geen toestemming om te schrijven in de doeldirectory.',
    'NO_USER_FILE'          => 'Er is geen bestand opgegeven om te uploaden.',
    'BAD_FORM'              => 'Het HTML-formulier bevat niet de volgende benodigde '.
           'eigenschappen: method="post" enctype="multipart/form-data".',
    'E_FAIL_COPY'           => 'Het tijdelijke bestand kon niet gekopieerd worden.',
    'E_FAIL_MOVE'           => 'Het bestand kon niet verplaatst worden.',
    'FILE_EXISTS'           => 'Het doelbestand bestaat al.',
    'CANNOT_OVERWRITE'      => 'Het doelbestand bestaat al, en kon niet worden overschreven.',
    'NOT_ALLOWED_EXTENSION' => 'Niet toegestane bestands-extensie.',
    'PARTIAL'               => 'Het bestand is slechts gedeeltelijk geupload.',
    'ERROR'                 => 'Upload fout:',
    'DEV_NO_DEF_FILE'       => 'Deze bestandsnaam is niett gedefineerd in het formulier als &lt;input type="file" name=?&gt;.',
);
?>
