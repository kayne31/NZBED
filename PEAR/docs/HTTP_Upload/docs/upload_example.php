<?xml version="1.0" encoding="utf-8" ?>
<html>
 <head>
  <title>HTTP_Upload example</title>
 </head>
 <body>
  <form action="<?php echo $_SERVER['PHP_SELF'];?>?submit=1" method="post" enctype="multipart/form-data">
   Send these files:<br/>
   <input type="hidden" name="MAX_FILE_SIZE" value="100000" />
   
   <input name="userfile" type="file"> &lt;-<br/>
   <input name="otherfile[]" type="file"><br/>
   <input name="otherfile[]" type="file"><br/>
   <input type="submit" value="Send files"/>
  </form>
<?php
error_reporting(E_ALL);
if (!isset($_GET["submit"])) {
  exit;
}
require 'HTTP/Upload.php';
echo '<pre>';

$upload = new Http_Upload('de');
$file = $upload->getFiles('userfile');
if (PEAR::isError($file)) {
    die ($file->getMessage());
}
if ($file->isValid()) {
    $file->setName('uniq');$file->setName('test.php');
    $dest_dir = './uploads/';
    $dest_name = $file->moveTo($dest_dir);
    if (PEAR::isError($dest_name)) {
        die ($dest_name->getMessage());
    }
    $real = $file->getProp('real');
    echo "Uploaded $real as $dest_name in $dest_dir\n";
} elseif ($file->isMissing()) {
    echo "No file selected\n";
} elseif ($file->isError()) {
    echo $file->errorMsg() . "\n";
}
print_r($file->getProp());
echo '</pre>';
?>
 </body>
</html>
