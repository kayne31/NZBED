<?php
$errorCodes = array();
$errorCodes['fr'] = array(
    'TOO_LARGE'             => "Le fichier est trop gros. La taille maximum autoris&eacute;e est: $maxsize bytes.",
    'MISSING_DIR'           => 'Le r&eacute;pertoire de destination n\'est pas d&eacute;fini.',
    'IS_NOT_DIR'            => 'Le r&eacute;pertoire de destination n\'existe pas ou il s\'agit d\'un fichier r&eacute;gulier.',
    'NO_WRITE_PERMS'        => 'Le r&eacute;pertoire de destination n\'a pas les droits en &eacute;criture.',
    'NO_USER_FILE'          => 'Vous n\'avez pas s&eacute;lectionn&eacute; de fichier &agrave; envoyer.',
    'BAD_FORM'              => 'Le formulaire HTML ne contient pas les attributs requis : '.
           ' method="post" enctype="multipart/form-data".',
    'E_FAIL_COPY'           => 'L\'enregistrement du fichier temporaire a &eacute;chou&eacute;.',
    'E_FAIL_MOVE'           => 'Impossible de d&eacute;placer le fichier.',
    'FILE_EXISTS'           => 'Le fichier de destination existe d&eacute;j&agrave;.',
    'CANNOT_OVERWRITE'      => 'Le fichier de destination existe d&eacute;j&agrave; et ne peux pas &ecirc;tre remplac&eacute;.',
    'NOT_ALLOWED_EXTENSION' => 'Le fichier a une extension non autoris&eacute;e.',
    'PARTIAL'               => null,
    'ERROR'                 => null,
    'DEV_NO_DEF_FILE'       => null,
);
?>
