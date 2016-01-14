<?php
define("mysqlDB", "userDB");
define("mysqlTable", "users");
define("root", "root");
define("rootpw", hash('sha256', 'root'));

#letsencrypt section
define("certPath", "/etc/letsencrypt/live/");
define("certPathArchive", "/etc/letsencrypt/archive/");
define("apacheConf", "/etc/nginx/sites-enabled/apache");
define("apacheSSL", "/etc/nginx/sites-enabled/apache-ssl");
define("domainFile", dirname(__FILE__) . "/activeDomains");
define("dockerCmd", "docker run -it --rm -p 4431:443 -p 801:80 --name letsencrypt -v /etc/letsencrypt:/etc/letsencrypt -v /var/lib/letsencrypt:/var/lib/letsencrypt -v /export/.well-known:/var/www/html/.well-known zawiw/letsencrypt --server https://acme-v01.api.letsencrypt.org/directory -a webroot --webroot-path /var/www/html --email zawiw.webadmin@uni-ulm.de --text --agree-tos -d [DOMAIN] auth");

#Edit restartNginx() in functions.php
# run init.php for database
?>
