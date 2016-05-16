HTTP Fragment
=============

The *HttpFragmentServiceProvider* provides support for the Symfony fragment
sub-framework, which allows you to embed fragments of HTML in a template.

.. warning::

    This service provider only work with Symfony 2.4+.

Parameters
----------

* **fragment.path**: The path to use for the URL generated for ESI and
  HInclude URLs (``/_fragment`` by default).

* **uri_signer.secret**: The secret to use for the URI signer service (used
  for the HInclude renderer).

* **fragment.renderers.hinclude.global_template**: The content or Twig
  template to use for the default content when using the HInclude renderer.

Services
--------

* **fragment.handler**: An instance of `FragmentHandler
  <http://api.symfony.com/master/Symfony/Component/HttpKernel/Fragment/FragmentHandler.html>`_.

* **fragment.renderers**: An array of fragment renderers (by default, the
  inline, ESI, and HInclude renderers are pre-configured).

Registering
-----------

.. code-block:: php

    $app->register(new Silex\Provider\HttpFragmentServiceProvider());

Usage
-----

.. note::

    This section assumes that you are using Twig for your templates.

Instead of building a page out of a single request/controller/template, the
fragment framework allows you to build a page from several
controllers/sub-requests/sub-templates by using **fragments**.

Including "sub-pages" in the main page can be done with the Twig ``render()``
function:

.. code-block:: jinja

    The main page content.

    {{ render('/foo') }}

    The main page content resumes here.

The ``render()`` call is replaced by the content of the ``/foo`` URL
(internally, a sub-request is handled by Silex to render the sub-page).

Instead of making internal sub-requests, you can also use the ESI (the
sub-request is handled by a reverse proxy) or the HInclude strategies (the
sub-request is handled by a web browser):

.. code-block:: jinja

    {{ render(url('route_name')) }}

    {{ render_esi(url('route_name')) }}

    {{ render_hinclude(url('route_name')) }}
