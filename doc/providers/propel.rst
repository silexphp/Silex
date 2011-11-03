PropelServiceProvider
======================

The *PropelServiceProvider* provides integration with `Propel Orm
<http://www.propelorm.org>`.


Parameters
----------

* **propel.path** (optional): Path to were Propel.php file is located. Usually, for 
  PEAR installation, is ``propel`` while for Git installation is 
  ``vendor/propel/runtime/lib``. 
  Defaults to ``/full/project/path/vendor/propel/runtime/lib``.

* **propel.config_file** (optional): The name of Propel configuration file with full path.
  Defaults to ``/full/project/path/build/conf/projectname-conf.php`` 

* **propel.model_path** (optional): Path to where model classes are located.
  Defaults to ``/full/project/path/build/classes``
  
* **propel.internal_autoload** (optional): Setting to true, forces Propel to use 
  its own internal autoloader, instead of Silex one, to load model classes. 
  Defaults to false
  
  
.. note::

    It's strongly recommanded to use **absolute paths** for previous options.


Services
--------

No service is provided.

Propel configures and manages itself by ** static ** methods, so no service 
is registered into Application.
Simply, the PropelServiceProvider class initializes Propel in a more "Silex-ian" way.


Registering
-----------

Make sure you place a copy of *Propel orm* in ``vendor/propel`` or install it
by PEAR. For more informations consult the Propel documentation http://www.propelorm.org/documentation/01-installation.html::

    $app->register(new Silex\Provider\PropelServiceProvider(), array(
            'propel.path'        => __DIR__.'/path/to/Propel.php',
            'propel.config_file' => __DIR__.'/path/to/myproject-conf.php,
            'propel.model_path'  => __DIR__.'/path/to/model/classes',
    ));
    
Alternatively, if you installed Propel by Git in ``vendor/propel`` and
you built your model with default Propel generator options:

    $app->register(new Silex\Provider\PropelServiceProvider());


.. note::

  We can consider "default" Propel generator options:
  
  * Put ``build.properties`` and ``schema.xml`` files into the main directory project,
    usually where file ``index.php`` is located.
  * In ``build.properties`` file, define only propel.database, propel.project 
    and propel.namespace.autopackage properties.



Usage
-----

Build the model, according to Propel documentation, instantiate the model classes you need
and use them. That's all. 

By default, PropelServiceProvider initializes Propel to use Silex autoloader to autoloader
model classes. Of course, Silex autoloader needs the model to be built with this option:

    propel.namespace.autopackage = true


If you plan to build your model without using namespaces, you need to set to true 
the option propel.internal_autoload.



For more information, consult the `Propel documentation
<http://www.propelorm.org/documentation/>`_.
