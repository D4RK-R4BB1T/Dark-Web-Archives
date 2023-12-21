Some required software is constantly updated and changed so you should always look for up-to-date version of software online.
You do not need to follow this tutorial. You can host **eckmar** on whatever server or system you want as long as your server meets the requiremnets.

<details>
  <summary>If your VPS doesn't have 2GB of RAM</summary>
    
  ```
  
If this is the case, you can use your disk memory as RAM using swap. Before continuing with this tutorial, check if your Ubuntu installation already has swap enabled by typing:

sudo swapon --show

If the output is empty, it means that your system does not have swap space enabled.
Otherwise, if you get something like below, you already have swap enabled on your machine.


NAME      TYPE      SIZE USED PRIO
/dev/sda2 partition 1.9G   0B   -2

Although possible, it is not common to have multiple swap spaces on a single machine.

The user you are logged in as must have sudo privileges to be able to activate swap. In this example, we will add 1G swap. If you want to add more swap, replace 1G with the size of the swap space you need. Perform the steps below to add swap space on Ubuntu 18.04 LTS.

Start by creating a file which will be used for swap:

sudo fallocate -l 2G /swapfile

If fallocate is not installed or you get an error message saying fallocate failed: Operation not supported then use the following command to create the swap file:


sudo dd if=/dev/zero of=/swapfile bs=2048 count=1048576

Only the root user should be able to write and read the swap file. Set the correct permissions by typing:

sudo chmod 600 /swapfile

Use the `mkswap` utility to set up a Linux swap area on the file:

sudo mkswap /swapfile

Activate the swap file using the following command:

sudo swapon /swapfile

To make the change permanent open the `/etc/fstab` file:

sudo nano /etc/fstab

and paste the following line:

/swapfile swap swap defaults 0 0


Verify that the swap is active by using either the swapon or the free command, as shown below:

sudo swapon --show


NAME      TYPE  SIZE   USED PRIO
/swapfile file 2048M 507.4M   -1
```
  
</details>

# Installation

Most of this will be simple copy-paste commands that you enter in your VPS. I'm writing this tutorial based on Ubuntu 18.04. When you first login on your VPS run:
```
sudo apt-get update
```

# Nginx
You can use any web server you want (Apache for example) but I will use Nginx. To install it run:
```
sudo apt-get install nginx
```
After installation is done we need to allow nginx in firewall by running:
```
sudo ufw allow 'Nginx HTTP'
```
After both steps are done, you should check whats your VPS IP address and enter that IP in a browser. You should see `welcome to nginx !` page. If you do see it, nginx is installed correctly.

# MySQL
Marketplace supports multiple databases like: MySQL,PostgreSQL, SQLite, SQL Server We will use MySQL.
```
sudo apt-get install mysql-server
```
After MySQL is installed, run
```
mysql_secure_installation
```
that will guide you trough securing your MySQL connection.

After secure installation is done, we need to create database for Marketplace by running series of commands:
```
mysql -u root -p
```
```
CREATE DATABASE marketplace DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
```
```
CREATE USER 'INSERT_YOUR_USERNAME'@'localhost' IDENTIFIED BY 'INSERT_YOUR_PASSWORD';
```
```
GRANT ALL PRIVILEGES ON * . * TO 'INSERT_YOUR_USERNAME'@'localhost';
```
```
FLUSH PRIVILEGES;
```
```
exit
```
If afterwards in the installation process you are not able to connect to the MySQL database because of authentication problems, change the root password: https://support.rackspace.com/how-to/mysql-resetting-a-lost-mysql-root-password/
# PHP
We need to install PHP (PHP-FPM) to run our code:
```
sudo apt-get install php7.2-fpm php-mysql
```
After the installation is done, we can check if php is correctly installed by running:
```
php -v
```
It should say PHP 7.2

We need to edit `php.ini` file. We can do that by runnin the command (assuming you installed php7.2, if you installed other version change that parameter)
```
sudo nano /etc/php/7.2/fpm/php.ini
```
Inside this file, there is commented line # cgi.fix_pathinfo=1 You need to uncomment the line and set value to cgi.fix_pathinfo=0 (without #)

In order for changes to take effect, php-fpm must be restarted:
```
sudo systemctl restart php7.2-fpm
```


Now we need to install some PHP extensions that are required by Marketplace as well as composer and unzip tools.
```
sudo apt-get install php7.2-mbstring php7.2-xml php7.2-xmlrpc php7.2-gmp php7.2-curl php7.2-gd composer unzip -y
```
(Above code is single command)

# Elasticsearch

Marketplace uses Elasticsearch software that provices great search speeds and flexibility. Elasticsearch requires Java in order to run.

# Oracle JDK
Update apt
```
sudo apt update
```
Install Java:
```
sudo apt-get install openjdk-8-jdk
```
Now we need to use that path and create environment variable:
```
echo "JAVA_HOME=$(which java)" | sudo tee -a /etc/environment
```
```
source /etc/environment
```
To check if everything is working enter:
```
echo $JAVA_HOME
```
The command should give same path as before as output.

# Elasticsearch installation

Now that java is installed, we can proceed with installation of Elasticsearch.
```
wget https://download.elastic.co/elasticsearch/release/org/elasticsearch/distribution/deb/elasticsearch/2.3.1/elasticsearch-2.3.1.deb
```
(Above code is single command)

Download .deb package and install it with:
```
sudo dpkg -i elasticsearch-2.3.1.deb
```

We want Elasticsearch service to start when system boots up, so we enter:
```
sudo systemctl enable elasticsearch.service
```
Now we need to start it up.
```
sudo systemctl start elasticsearch
```
Give it 10-15 seconds from last command, and then run:
```
curl -X GET "localhost:9200"
```
If you see information about your Elasticsearch engine, then installation is completed successfully.

# Elasticsearch installation error
Elasticsearch has some problems on servers with low memory. In order to make it work we need to limit max memory Java is using. To check if this is an issue run:
```
sudo service elasticsearch status
```

If you see `There is insufficient memory for the Java Runtime...` inside the text, continue, if not then your installation is not done properly and you should remove all Elasticsearch packages and go back to installing it from the start.

Enter:

```
edit /etc/elasticsearch/jvm.options
```

Change to lower memory:
```
-Xms512m -Xmx512m
```

Then restart Elasticsearch:
```
sudo systemctl restart elasticsearch
```

Give it 10-15 seconds and then run:
```
curl -X GET "localhost:9200"
```

If you see information about your Elasticsearch engine, then installation is completed successfully.

# Redis
This step is optional, but will greatly increase your app performance.
```
sudo apt install redis-server
```
After redis installation is done open redis config file:
```
sudo nano /etc/redis/redis.conf
```

In there find supervised and change it from supervised no to supervised systemd and save the file.
Reload Redis with:
```
sudo systemctl restart redis.service
```
And check if its running with
```
sudo systemctl status redis.service
```
To check if Redis is installed correctly enter:
```
redis-cli
```
It should open Redis interface running on port 6379. By entering `ping` you should get response `PONG`.
If everything is fine, type exit and exit redis-cli.

# Node and NPM
We need NodeJS and NPM in order to compile our client side css files.

Install NodeJS:
```
sudo apt-get install -y nodejs
```
Install NPM:
```
sudo apt-get install -y npm
```
To check if they are installed properly run:
```
node -v
```
```
npm -v
```
(Above code are 2 commands)
# Files

Now we need to copy the files to the server.
```
cd /var/www/
```
```
git clone https://github.com/nomiac-mobile/peralta.git
```
# Permissions

After files are copied we need to give them permissions.
```
sudo chown -R www-data:www-data /var/www/peralta/public
```
```
sudo chmod 755 /var/www
```
```
sudo chmod -R 755 /var/www/peralta/bootstrap/cache
```
```
sudo chmod -R 755 /var/www/peralta/storage
```
```
sudo chown -R $USER:www-data /var/www/peralta/storage
```
```
sudo chown -R $USER:www-data /var/www/peralta/bootstrap/cache
```
```
sudo chmod -R 775 /var/www/peralta/storage
```
```
sudo chmod -R 775 /var/www/peralta/bootstrap/cache
```

Make this folder: (used for product pictures):
```
sudo mkdir /var/www/peralta/storage/public/
sudo mkdir /var/www/peralta/storage/public/products
```

And give it permissions
```
sudo chmod -R 755 /var/www/peralta/storage/public/products
```
```
sudo chgrp -R www-data /var/www/peralta/storage/public/products
```
```
sudo chmod -R ug+rwx /var/www/peralta/storage/public/products
```
(Above code are 3 commands)

# Nginx Config

Nginx is installed but we didn't point it towards marketplace. To edit nginx config run:
```
sudo nano /etc/nginx/sites-available/default
```
I won't explain what most of the stuff does, so here is an example of configured file. Delete everything and paste this:
```
server {
        listen 80;
        listen [::]:80;
        listen 443;
        listen [::]:443;
root /var/www/peralta/public;
index index.php index.html index.htm index.nginx-debian.html;
server_name domain.com;
location / {
try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
try_files $uri =404;
fastcgi_split_path_info ^(.+\.php)(/.+)$;
fastcgi_pass unix:/run/php/php7.2-fpm.sock;
fastcgi_index index.php;
fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; include fastcgi_params;
} }
```
After you change the parameters to reflect your environment run:
```
sudo nginx -t
```

If your config file is correct output should be:
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

# Installation

After everything above is done, change current directory to the directory name you previously chose (I used **peralta**) and run series of commands to install all required dependencies:
```
cd /var/www/peralta
```
```
composer install
```
```
npm install
```
```
npm run prod
```
```
cp .env.example .env
```
```
php artisan key:generate
```
Then open your .env file and insert database connection details:
```
sudo nano .env
```
Example of database configuration:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marketplace
DB_USERNAME=INSERT_YOUR_USERNAME
DB_PASSWORD=INSERT_YOUR_PASSWORD
```
If you did install redis, change driver from sync to redis:
```
CACHE_DRIVER=redis
```
Now you can try running:
```
php artisan migrate
```

Now, you can create some dummy data, with:
```
php artisan db:seed
```

If both commands ran fine, your connection to database is configured fine. If you want to get rid of dummy data, run:
```
php artisan migrate:fresh
```

Now, run this to link public directory with storage:

```
php artisan storage:link
```

Restart Nginx:
```
sudo service nginx restart
```

Your basic marketplace is working now, `Congratulations !`

# Connecting coins

Nomiac has support for various coins. Each coin has its on prefix in .env file as well as connection parameters. Connection paramters are:
```
HOST
PORT
USERNAME
PASSWORD
```
And coin prefixes are:
```
Bitcoin - BITCOIND
Litecoin - LITECOIN
Monero - MONERO
Pivx - PIVX
Dash - DASH
Verge - VERGE
Bitcoin Cash - BITCOIN_CASH
```

Knowing this, you can input connection parameters in .env accordingly. For example, for Bitcoin you would enter `BITCOIND_HOST=server_ip`, or for `Dash DASH_PASSWORD=password`.

# Marketplace configuration

Marketplace configuration is split into multiple files located in config folder. Main one is marketplace.php You will find most of the config options described or self-explanatory. Other than marketplace.php You can configure levels and experience in experience.php and marketplace addresses for receiving profits in coins.php
