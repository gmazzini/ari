<?php

function myauth($sez,cell){
  $otp=sprintf("%05d",rand(0,99999));
  echo "<span id='yyy'>Sezione $sez, Utente $cell<br>Invia via whatapp il codice $otp al numero di autenticazione 3770867586 entro 90 secondi\n</span>";
  ob_flush(); flush();
  $epoch=time();
  $look=0;
  for($i=0;$i<90 && $look==0;$i++){
    $fp=fopen("/home/www/data/auth/39".$cell,"r");
    if($fp!=NULL){
      $line=fgets($fp);
      $vv=explode(",",$line);
      if($vv[0]==$otp && $epoch-$vv[1]<90){$look=1; break;}
      fclose($fp);
    }
    sleep(1);
  }
  return $look;
}

?>
