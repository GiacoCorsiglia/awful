# Awful Example Site

An example [WordPress](https://wordpress.org) site using the [Awful](https://github.com/GiacoCorsiglia/awful) framework.

## Local Setup

- Ensure you have run `composer install` in the parent directory.
- Install Laravel Valet following [these steps](https://laravel.com/docs/valet).
- Install MySQL following the steps on the Valet documentation.
- Create the database:
  ```sh
  mysql -u root -e "CREATE DATABASE awful_example;"
  ```
- Activate this site with Valet:
  ```sh
  cd public
  valet link awful
  ```
- Temporarily set the `AWFUL_INSTALLED` constant to `false` in `public/wp-content/mu-plugins/awful-bootstrap.php`.
- Navigate to http://awful.test and complete the WordPress installation.
- Install the [WordPress CLI](https://wp-cli.org/#installing)
- Install Awful's database tables:
  ```sh
  cd public
  php ../wp-cli.phar awful install
  ```
- Reset the `AWFUL_INSTALLED` constant to `true`.
