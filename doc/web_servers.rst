Webserver Configuration
=======================

Apache
------

If you are using Apache, make sure ``mod_rewrite`` is enabled and use the
following ``.htaccess`` file:

.. code-block:: apache

    <IfModule mod_rewrite.c>
        Options -MultiViews

        RewriteEngine On
        #RewriteBase /path/to/app
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [QSA,L]
    </IfModule>

.. note::

    If your site is not at the webroot level you will have to uncomment the
    ``RewriteBase`` statement and adjust the path to point to your directory,
    relative from the webroot.

Alternatively, if you use Apache 2.2.16 or higher, you can use the
`FallbackResource directive`_ to make your .htaccess even easier:

.. code-block:: apache

    FallbackResource /index.php

.. note::

    If your site is not at the webroot level you will have to adjust the path to
    point to your directory, relative from the webroot.

nginx
-----

The **minimum configuration** to get your application running under Nginx is:

.. code-block:: nginx

    server {
        server_name domain.tld www.domain.tld;
        root /var/www/project/web;
    
        location / {
            # try to serve file directly, fallback to front controller
            try_files $uri /index.php$is_args$args;
        }
    
        # If you have 2 front controllers for dev|prod use the following line instead
        # location ~ ^/(index|index_dev)\.php(/|$) {
        location ~ ^/index\.php(/|$) {
            # the ubuntu default
            fastcgi_pass   unix:/var/run/php5-fpm.sock;
            # for running on centos
            #fastcgi_pass   unix:/var/run/php-fpm/www.sock;
    
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param HTTPS off;
        
            # Prevents URIs that include the front controller. This will 404:
            # http://domain.tld/index.php/some-path
            # Enable the internal directive to disable URIs like this
            # internal;
        }

        #return 404 for all php files as we do have a front controller
        location ~ \.php$ {
            return 404;
        }
    
        error_log /var/log/nginx/project_error.log;
        access_log /var/log/nginx/project_access.log;
    }

IIS
---

If you are using the Internet Information Services from Windows, you can use
this sample ``web.config`` file:

.. code-block:: xml

    <?xml version="1.0"?>
    <configuration>
        <system.webServer>
            <defaultDocument>
                <files>
                    <clear />
                    <add value="index.php" />
                </files>
            </defaultDocument>
            <rewrite>
                <rules>
                    <rule name="Silex Front Controller" stopProcessing="true">
                        <match url="^(.*)$" ignoreCase="false" />
                        <conditions logicalGrouping="MatchAll">
                            <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />
                        </conditions>
                        <action type="Rewrite" url="index.php" appendQueryString="true" />
                    </rule>
                </rules>
            </rewrite>
        </system.webServer>
    </configuration>

Lighttpd
--------

If you are using lighttpd, use this sample ``simple-vhost`` as a starting
point:

.. code-block:: lighttpd

    server.document-root = "/path/to/app"

    url.rewrite-once = (
        # configure some static files
        "^/assets/.+" => "$0",
        "^/favicon\.ico$" => "$0",

        "^(/[^\?]*)(\?.*)?" => "/index.php$1$2"
    )

.. _FallbackResource directive: http://www.adayinthelifeof.nl/2012/01/21/apaches-fallbackresource-your-new-htaccess-command/

PHP 5.4
-------

PHP 5.4 ships with a built-in webserver for development. This server allows
you to run silex without any configuration. However, in order to serve static
files, you'll have to make sure your front controller returns false in that
case::

    // web/index.php

    $filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
    if (php_sapi_name() === 'cli-server' && is_file($filename)) {
        return false;
    }

    $app = require __DIR__.'/../src/app.php';
    $app->run();


Assuming your front controller is at ``web/index.php``, you can start the
server from the command-line with this command:

.. code-block:: text

    $ php -S localhost:8080 -t web web/index.php

Now the application should be running at ``http://localhost:8080``.

.. note::

    This server is for development only. It is **not** recommended to use it
    in production.
