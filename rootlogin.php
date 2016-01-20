<?php
require_once dirname( __FILE__ ) .'/config.php';
require_once dirname( __FILE__ ) .'/functions.php';

?>
<div class="content">
<div class="logout"><a href='?logout=true'>Logout</a></div>
<?php
$boolPost = False;
if(isset($_POST['domain']) && isset($_POST['port']))
{
   if(empty($_POST['domain']) || empty($_POST['port']))
   {
      echo "<div class='warning'>Registrierung fehlgeschlagen. Es ist mindestens ein Feld leer!</div>";
      $boolPost = False;
   }else{
      $boolPost = True;
      if(writeConfigFile($_POST['domain'], $_POST['port']) === TRUE)
      {
         echo "<div class='success'>Registrierung erfolgreich</div>";
         restartNginx();
	 sleep(2);
         header('Location:'.$_SERVER['PHP_SELF']);
      } else {
         echo "<div class='error'>Registrierung fehlgeschlagen. Datei nicht gefunden</div>";
         $boolPost = False;
         header('Location:'.$_SERVER['PHP_SELF']);
      }
   }
}
$formArray = array("alterDomain", "alterPort");
if(isset($_POST['alterDomain']) && isset($_POST['alterPort'])) 
{
   $boolPost = True;
   $entryCount = sizeof(array_map("count", $_POST['alterDomain']));
   for($i = 0; $i<$entryCount; $i++) {
      foreach ($formArray as $entry) {
         if(empty($_POST[$entry][$i])){
            echo "<div class='warning'>Ändern fehlgeschlagen. Es ist mindestens ein Feld leer!</div>";
            $boolPost = False;
            return;
         }
      }
   }
   $boolPost = True;
   //get domains in cronjobfile
   $oldDomainArray = explode("\n", file_get_contents(domainFile));
   //unset empty field because of newLine
   if(($key = array_search('', $oldDomainArray)) !== False)
      unset($oldDomainArray[$key]);
   
   for($i = 0; $i<$entryCount; $i++) {
      $altertmp = array();
      foreach ($formArray as $entry) {
         $altertmp[] = $_POST[$entry][$i];
      }
      alterInput($altertmp, $i);
      if(($key = array_search($altertmp[0], $oldDomainArray)) !== False)
         unset($oldDomainArray[$key]);
   }
   if(!empty($oldDomainArray)){
      //reindex array
      $oldDomainArray = array_values($oldDomainArray);
      foreach ($oldDomainArray as $value) {
         rmFolder(certPath . $value . "/");
         rmFolder(certPathArchive . $value . "/");
      }
   }
   restartNginx();
   header('Location:'.$_SERVER['PHP_SELF']);
}
?>
<div class="newDomain">
<h1>Neue Domain anlegen</h1>
   <div class="newDomainForm">
      <form action="" method="post" id="registerDomainForm">
         <div><label for="domain">Domain:</label><input type="text" name="domain", id="domain" /></div>
         <div><label for="port">Port:</label><input type="text" name="port", id="port" value="8000"/></div>
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
readConfigFile($boolPost);
?>
   <div class="alterDomain">
      <input type="submit" name="alterDomainBtn" value="Speichern" id="alterDomainBtn" />
   </div>
</form>
</div>
</div>
