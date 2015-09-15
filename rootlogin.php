<?php
require_once dirname( __FILE__ ) .'/config.php';
require_once dirname( __FILE__ ) .'/functions.php';

?>
<div class="content">
<div class="logout"><a href='?logout=true'>Logout</a></div>
<?php
if(isset($_POST['domain']) && isset($_POST['port']) &&isset($_POST['ssl_certificate']) && isset($_POST['ssl_certificate_key']))
{
   if(empty($_POST['domain']) || empty($_POST['port']) || empty($_POST['ssl_certificate']) || empty($_POST['ssl_certificate_key']))
   {
      echo "<div class='warning'>Registrierung fehlgeschlagen. Es ist mindestens ein Feld leer!</div>";

   }else{
      if(writeConfigFile($_POST['domain'], $_POST['port'], $_POST['ssl_certificate'], $_POST['ssl_certificate_key']))
      {
         echo "<div class='success'>Registrierung erfolgreich</div>";
         restartNginx();
         header('Location:'.$_SERVER['PHP_SELF']);
      } else {
         echo "<div class='error'>Registrierung fehlgeschlagen. Datei nicht gefunden</div>";
         header('Location:'.$_SERVER['PHP_SELF']);
      }
   }
}
?>
<div class="newDomain">
<h1>Neue Domain anlegen</h1>
   <div class="newDomainForm">
      <form action="" method="post" id="registerDomainForm">
         <div><label for="domain">Domain:</label><input type="text" name="domain", id="domain" /></div>
         <div><label for="port">Port:</label><input type="text" name="port", id="port" value="8000"/></div>
         <div><p class="formfield"><label for="ssl_certificate">SSL-Zertifikat:</label><textarea rows="10" cols="50" name="ssl_certificate", id="ssl_certificate" ></textarea></p></div>
         <div><p class="formfield"><label for="ssl_certificate_key">SSL-Key:</label><textarea rows="10" cols="50" name="ssl_certificate_key", id="ssl_certificate_key" ></textarea></p></div>
         <div>
            <input type="submit" name="registerDomain" value="Anlegen" id="registerDomain" />
         </div>
      </form>
   </div>
</div>
<hr>
<input id="toggle" name="toggle" onclick="toggle()" type="checkbox" value="1" /> Domains bearbeiten </br>
<h1>Eingetragene Domains</h1>
<div id="toggleDiv">
<form action="" method="post" id="alterForm">
<?php
readConfigFile();
?>
   <div class="alterDomain">
      <input type="submit" name="alterDomainBtn" value="Speichern" id="alterDomainBtn" />
   </div>
</form>
</div>
<?php
$formArray = array("alterDomain", "alterPort", "alterCertPath", "altersslCert", "alterKeyPath", "altersslKey");
if(isset($_POST['alterDomain']) && isset($_POST['alterPort']) && isset($_POST['alterCertPath']) && isset($_POST['altersslCert']) && isset($_POST['alterKeyPath']) && isset($_POST['altersslKey'])) {
   $entryCount = sizeof(array_map("count", $_POST['alterDomain']));
   for($i = 0; $i<$entryCount; $i++) {
      foreach ($formArray as $entry) {
         if(empty($_POST[$entry][$i])){
            echo "<div class='warning'>Ändern fehlgeschlagen. Es ist mindestens ein Feld leer!</div>";
            return;
         }
      }
   }
   if(!createBackup()){
      echo "<div class='warning'>Abbruch - Backup fehlgeschlagen</div>";
      return;
   }
   rmFolder(certDest);
   if(!file_exists(certDest))
      @mkdir(certDest);
   for($i = 0; $i<$entryCount; $i++) {
      $altertmp = array();
      foreach ($formArray as $entry) {
         $altertmp[] = $_POST[$entry][$i];
      }
      alterInput($altertmp, $i);
   }
   restartNginx();
   header('Location:'.$_SERVER['PHP_SELF']);
}

?>
</div>
