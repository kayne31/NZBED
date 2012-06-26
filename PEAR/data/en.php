<?php
$errorCodes = array();
$errorCodes['en'] = array(
    'TOO_LARGE'             => "File size too large. The maximum permitted size is: $maxsize bytes.",
    'MISSING_DIR'           => 'Missing destination directory.',
    'IS_NOT_DIR'            => 'The destination directory doesn\'t exist or is a regular file.',
    'NO_WRITE_PERMS'        => 'The destination directory doesn\'t have write perms.',
    'NO_USER_FILE'          => 'You haven\'t selected any file for uploading.',
    'BAD_FORM'              => 'The html form doesn\'t contain the required method="post" enctype="multipart/form-data".',
    'E_FAIL_COPY'           => 'Failed to copy the temporary file.',
    'E_FAIL_MOVE'           => 'Impossible to move the file.',
    'FILE_EXISTS'           => 'The destination file already exists.',
    'CANNOT_OVERWRITE'      => 'The destination file already exists and could not be overwritten.',
    'NOT_ALLOWED_EXTENSION' => 'File extension not permitted.',
    'PARTIAL'               => 'The file was only partially uploaded.',
    'ERROR'                 => 'Upload error:',
    'DEV_NO_DEF_FILE'       => 'This filename is not defined in the form as &lt;input type="file" name=?&gt;.',
);
?>
