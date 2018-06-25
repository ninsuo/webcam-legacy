Webcam viewer
=============

## Prerequisite

Your camera should send a new picture in a directory of your server every
second for the best live possible. Those pictures should have the read
permission for www-data (easy, make sure they are `-rw-r--r--`).

## Installation

1) Install and run the project:

```sh
git clone https://github.com/ninsuo/webcam.git
cd webcam
php -r "readfile('https://getcomposer.org/installer');" | php
php composer.phar install
php app/console doctrine:schema:create
```

2) To use the login system, you need to get your google client ID and secret:

| Provider       | Setup URL                                     |
| -------------- | --------------------------------------------- |
| Google         | https://console.developers.google.com/project |

3) Be enabled and admin! Once you created your first user, you can run the following commands:

```sh
# list your users, from here you can find your id (should be 1, but stay safe)
php app/console user:list

# enable user with id = 1
php app/console user:enable 1

# set user with id = 1 as admin
php app/console user:admin 1
```

Note that those commands are toggles, so running `php app/console user:admin 1` a second time will remove admin
privileges for the given user.

## Development

In order to develop this project on a Mac, you may need to mount the server that pocesses your images
using MacFuse/SSHFS.

```
sshfs user@host:/remote/path/to/webcams ~/local-directory -o cache=no,reconnect,defer_permissions,noappledouble
```

## License

- This project is released under the MIT license

- Fuz logo is © 2013-2042 Alain Tiemblo

- Default image used on social meta tags is CC0
