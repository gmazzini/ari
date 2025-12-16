<?php

include "ari_local.php";
$con=mysqli_connect($dbhost,$dbuser,$dbpassword,$dbname);

$idlista="";
$raw=file_get_contents("php://input");
$data=json_decode($raw,true);
foreach($data as $key=>$value){
  if(strlen($key)==8){
    $idlista=$key;
    $sez=$value;
    break;
  }
}
if(strlen($idlista)<8)exit(0);

mysqli_query($con,"delete from rinnovi where idlista='{$idlista}'");
mysqli_query($con,"delete from listarinnovi where idlista='{$idlista}'");
mysqli_query($con,"insert into rinnovi (idlista,sezione,attivo,epoch) values ('{$idlista}','{$sez}',1,".time().")");
foreach($data as $key => $value) {
  if(strlen($key)==6){
    mysqli_query($con,"insert into listarinnovi (idlista,id,codice) values ('{$idlista}','{$key}',$value)");
  }
}

mysqli_close($con);

?>


