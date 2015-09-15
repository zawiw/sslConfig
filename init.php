<?php
require_once dirname( __FILE__ ) .'/config.php';
try{
   $db = new SQLite3(mysqlDB.".db");
   $db->exec("CREATE TABLE IF NOT EXISTS " .mysqlTable." (
         loginname VARCHAR(100) NOT NULL,
         pwhash TEXT NOT NULL,
         lastlogin BIGINT NOT NULL,
         session TEXT NOT NULL,
         randomnumber INT
         )");
   $db->exec("INSERT INTO ".mysqlTable." (loginname, pwhash, lastlogin, session) VALUES ('". root . "', '" . rootpw . "', '0', '0000');");
   $result = $db->query("SELECT * FROM " . mysqlTable);
   while ($row = $result->fetchArray()) {
      echo "<div>".$row['loginname'] . " entry created</div>";
   }
   $db = NULL;
   echo "Datenbank erfolgreich angelegt\n";
} catch(Exception $e) {
   echo "Fehler beim erstellen und schrewiben der Datenbank\n";
}
?>
