Introduction
============

Silex is a PHP microframework for PHP 5.3. It is built on the shoulders
of Symfony2 and Pimple and also inspired by sinatra.

A microframework provides the guts for building simple single-file apps.
Silex aims to be:

* *Concise*: Silex exposes a intuitive and concise API that is fun to use.

* *Extensible*: Silex has an extension system based around the Pimple
  micro service-container that makes it even easier to tie in third party
  libraries.

* *Testable*: Silex uses Symfony2's HttpKernel which abstracts request and
  response. This makes it very easy to test apps and the framework itself.
  It also respects the HTTP specification and encourages its proper use.

In a nutshell, you define controllers and map them to routes, all in one
step.

Let's go! ::

    require_once __DIR__.'/silex.phar';

    $app = new Silex\Application();

    $app->get('/hello/{name}', function($name) {
        return "Hello $name";
    });

    $app->run();

All that is needed to get access to the Framework is to include
``silex.phar``. This phar (PHP Archive) file will take care of the rest.

Next we define a route to ``/hello/{name}`` that matches for ``GET``
requests. When the route matches, the function is executed and the return
value is sent back to the client.

Finally, the app is run. It's really that easy!

Installation
------------

Installing Silex is as easy as it can get. Download the [`silex.phar`][2] file
and you're done!

License
-------

Silex is licensed under the MIT license.

.. code-block:: text

    Copyright (c) 2010 Fabien Potencier

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is furnished
    to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.
