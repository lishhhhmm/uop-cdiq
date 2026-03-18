# Guide to Run

Edit the values of the placeholders at ```.env``` file. Avoid using uppercase letters on DBMS variables for database and username.

Move your **certificate** and **private key** inside ```server/``` named ```cert``` and ```pkey``` respectively. One way to obtain the above files is via running the command below and following the prompts and directories mentioned when running:

```
sudo docker run -it --rm --name certbot -v "./certbot:/etc/letsencrypt" -v "./certbot:/var/lib/letsencrypt" -p 80:80 certbot/certbot certonly
```

Execute the following commands inside base directory of repository:

````
docker compose up -d --build
````

````
docker exec -it <server-container> bash
````

````
php .private/_admin/dbms/create.php
````

```
php .private/_admin/dbms/init_ops.php
```

```
php -r 'file_put_contents(".private/.superadmin_hash", password_hash("<password>", PASSWORD_BCRYPT));'
```

Finally, navigate to the super admin page (costas/os.php) and change the operators passwords.

# Bla bla bla

...
