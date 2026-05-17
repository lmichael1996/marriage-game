php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

composer install

sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql

sudo mkdir -p /var/lib/mysql
sudo chown -R mysql:mysql /var/lib/mysql
sudo chmod -R 750 /var/lib/mysql

sudo mysql -u root -p

CREATE DATABASE marriage_game;