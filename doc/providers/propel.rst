PropelServiceProvider
======================

The *PropelServiceProvider* provides integration with `Propel <http://www.propelorm.org>`.


Parameters
----------

* **propel.path** (optional): The path in wich Propel.php will be found. Usually, for 
  PEAR installation, it is ``propel`` while for Git installation it is 
  ``vendor/propel/runtime/lib``. 
  Default is ``/full/project/path/vendor/propel/runtime/lib``.

* **propel.config_file** (optional): The name of Propel configuration file with full path.
  Default is ``/full/project/path/build/conf/projectname-conf.php`` 

* **propel.model_path** (optional): Path to where model classes are located.
  Default is ``/full/project/path/build/classes``
  
* **propel.internal_autoload** (optional): Setting to true, forces Propel to use 
  its own internal autoloader, instead of Silex one, to load model classes. 
  Default is false
  
  
.. note::

    It's strongly recommanded to use **absolute paths** for previous options.


Services
--------

No service is provided.

Propel configures and manages itself by ** using ** static methods, so no service 
is registered into Application.
Actually, the PropelServiceProvider class initializes Propel in a more "Silex-ian" way.


Registering
-----------

Make sure you place a copy of *Propel* in ``vendor/propel`` or install it
through PEAR. For more informations consult the Propel documentation http://www.propelorm.org/documentation/01-installation.html::

    $app->register(new Silex\Provider\PropelServiceProvider(), array(
            'propel.path'        => __DIR__.'/path/to/Propel.php',
            'propel.config_file' => __DIR__.'/path/to/myproject-conf.php,
            'propel.model_path'  => __DIR__.'/path/to/model/classes',
    ));
    
Alternatively, if you 've installed Propel by Git in ``vendor/propel`` and
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



By default, PropelServiceProvider initializes Propel to use Silex autoloader to load
model classes. Of course, Silex autoloader needs the model to be built with namespaces, 
so be sure to set this property into build.properties file:
    
    propel.namespace.autopackage = true
    
before building your model.

If you plan to build your model without using namespaces, you need to force Propel to use
its internal autoloader. Do this by setting the option ``propel.internal_autoload`` to true.



For more information, consult the `Propel documentation
<http://www.propelorm.org/documentation/>`_.
