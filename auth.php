<?php

function myauth($sez,$cell){
  $otp=sprintf("%05d",rand(0,99999));
  echo "<span id='yyy'>Sezione $sez, Utente $cell<br>Invia via whatapp il codice <b>$otp</b> al numero di autenticazione 3770867586 entro 90 secondi<br><span style='color:red;'>In alternativa e in via fortemente sperimentale puoi inviare via SMS il codice <b>$otp</b> al numero di autenticazione 3793416173 entro 90 secondi</span>\n</span>";
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
