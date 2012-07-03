Webserver Configuration
=======================

Apache
------

If you are using Apache you can use a ``.htaccess`` file for this:

.. code-block:: apache

    <IfModule mod_rewrite.c>
        Options -MultiViews

        RewriteEngine On
        #RewriteBase /path/to/app
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [L]
    </IfModule>

.. note::

    If your site is not at the webroot level you will have to uncomment the
    ``RewriteBase`` statement and adjust the path to point to your directory,
    relative from the webroot.

Alternatively, if you use Apache 2.2.16 or higher, you can use the
`FallbackResource directive`_ so make your .htaccess even easier:

.. code-block:: apache

    FallbackResource index.php

nginx
-----

If you are using nginx, configure your vhost to forward non-existent
resources to ``index.php``:

.. code-block:: nginx

    server {
        index index.php

        location / {
            try_files $uri $uri/ /index.php;
        }

        location ~ index\.php$ {
            fastcgi_pass   /var/run/php5-fpm.sock;
            fastcgi_index  index.php;
            include fastcgi_params;
        }
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
you to run silex without any configuration. Assuming your front controller is
at ``web/index.php``, you can start the server from the command-line with this
command:

.. code-block:: text

    $ php -S localhost:8080 -t web

Now the application should be running at ``http://localhost:8080``.

.. note::

    This server is for development only. It is **not** recommended to use it
    in production.
