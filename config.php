<?php
define("mysqlDB", "userDB");
define("mysqlTable", "users");
define("root", "root");
define("rootpw", hash('sha256', '+bhUVeupUnp.,7PM'));

#letsencrypt section
define("certPath", "/etc/letsencrypt/live/");
define("certPathArchive", "/etc/letsencrypt/archive/");
define("apacheConf", "/etc/nginx/sites-available/apache");
define("apacheSSL", "/etc/nginx/sites-available/apache-ssl");
define("domainFile", dirname(__FILE__) . "/activeDomains");
#define("dockerCmd", "id");
define("dockerCmd", '/bin/bash -c "/usr/bin/docker run -i --rm -p 4431:443 -p 801:80 --name letsencrypt -v /etc/letsencrypt:/etc/letsencrypt -v /var/lib/letsencrypt:/var/lib/letsencrypt -v /export/.well-known:/var/www/html/.well-known zawiw/letsencrypt --server https://acme-v01.api.letsencrypt.org/directory -a webroot --webroot-path /var/www/html --email zawiw.webadmin@uni-ulm.de --text --agree-tos -d [DOMAIN] auth 2>&1"');
#define("dockerCmd", "/bin/bash docker.sh [DOMAIN]");
#define("dockerCmd", '/bin/bash -c "/usr/bin/docker ps"');

#Edit restartNginx() in functions.php
# run init.php for database
?>
