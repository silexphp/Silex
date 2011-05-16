AsseticExtension
================

The *AsseticExtension* provides powerful asset management
through Kris Wallsmith's `Assetic <https://github.com/kriswallsmith/assetic>`_
library.

Parameters
----------

* **assetic.options**: An associative array of assetic
  options.

* **assetic.options => path_to_web**: Location where to dump
  all generated files

* **assetic.options => debug** (defaults to false, optional): 

* **assetic.options => twig_support** (defaults to false, optional): Enables 
  the Assetic Twig extension when Silex Twix extension is also registered
  Turn the debug mode for Assetic on/off

* **assetic.options => formulae_cache_dir** (optional): When formulae_cache_dir is set, Assetic
  will cache assets generated trough formulae in this folder to improve performance. Remember,
  assets added trough the AssetManager need to care about their own cache.

* **assetic.class_path** (optional): Path to where the Assetic
  library is located.

* **assetic.filters** (optional): Used for configuring filters on registration, just provide an 'app protected'
  callback $app->protect(function($fm) { }) and add your filters inside the function to filter manager ($fm->set())

* **assetic.assets** (optional): Used for configuring assets on registration, just provide an 'app protected' 
  callback $app->protect(function($am) { }) and add your assets inside the function to asset manager ($am->set())

Services
--------

* **assetic**: Instance of AssetFactory for
  holding filters and assets (not formulae)

* **assetic.asset_manager**: Instance of AssetManager
  for adding assets (implements AssetInterface)

  Example usage::

    $asset = new FileAsset(__DIR__ . '/extra/*.css');
    $app['assetic.asset_manager']->set('extra_css', $asset);
    
* **assetic.filter_manager**: Instance of FilterManager
  for adding filters (implements FilterInterface)

  Example usage::

    $filter = new CssMinFilter();
    $app['assetic.asset_manager']->set('css_min', $filter);

* **assetic.asset_writer**: If you need it, feel free to use.

* **assetic.lazy_asset_manager**:  Instance of LazyAssetManager
  to enable passing-in assets as formulae

  Example usage::

    $app['assetic.lazy_asset_manager']->setFormula('extra_css', array(
        array(__DIR__ . '/extra/*.css'),
        array('yui_css'),
        array('output' => 'css/extra')  
    ));

Registering
-----------

Make sure you place a copy of *Assetic* in the ``vendor/assetic``
directory.

  Example registration and configuration::

    $app->register(new Silex\Extension\AsseticExtension(), array(
        'assetic.class_path' => __DIR__.'/vendor/assetic/src',
        'assetic.options' => array(
            'path_to_web'           => __DIR__ . '/assets',
            'twig_support'          => true
        ),
        'assetic.filters' => $app->protect(function($fm) {
            $fm->set('yui_css', new Assetic\Filter\Yui\CssCompressorFilter(
                '/usr/share/yui-compressor/yui-compressor.jar'
            ));
            $fm->set('yui_js', new Assetic\Filter\Yui\JsCompressorFilter(
                '/usr/share/yui-compressor/yui-compressor.jar'
            ));
        }),    
        'assetic.assets' => $app->protect(function($am, $fm) {
            $am->set('styles', new Assetic\Asset\AssetCache(
                new Assetic\Asset\GlobAsset(
                    __DIR__ . '/resources/css/*.css', 
                    array($fm->get('yui_css'))
                ),
                new Assetic\Cache\FilesystemCache(__DIR__ . '/cache/assetic')
            ));
            $am->get('styles')->setTargetUrl('css/styles.css');
        })
    ));

