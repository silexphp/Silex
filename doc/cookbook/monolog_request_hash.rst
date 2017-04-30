Adding a request hash using Monolog
===================================

Often times is useful to add some sort of hash or identifier to each request
in the logs so it's easier to aggregate or filter its lines with tools like grep
or more advanced ones like `Logstash
<https://www.elastic.co/products/logstash>`_.

This is something simple to achieve with Monolog by means of its Formatters and
Processors.

First up, we need to create a new Monolog Processor which is going to slightly
transform the record to be logged before it actually gets logged. At this stage,
we'll create the request hash if it hasn't been created already.

.. code-block:: php

    final class TokenProcessor
    {
        private $token = null;

        public function __invoke(array $record)
        {
            if (is_null($this->token)) {
                $this->token = uniqid();
            }

            $record['extra']['token'] = $this->token;

            return $record;
        }
    }

We're implementing the processor in a class instead of a function to keep the
same hash during the whole request every time a record is written in the log.
We also use a little trick to make the class a "callable" by implementing the
"__invoke" magic method.

Next step, is to create a Monolog Formatter that actually makes use of the
token created in the Processor. In this case, we're only going to extend the
existing "LineFormatter" but changing its default format.

.. code-block:: php

    final class TokenLineFormatter extends Monolog\Formatter\LineFormatter
    {
        const TOKEN_FORMAT = "[%datetime%][%extra.token%] %channel%.%level_name%: %message% %context%\n";

        public function __construct()
        {
            parent::__construct(self::TOKEN_FORMAT);
        }
    }

Finally, the only thing left to do is to link everything together in Monolog's
provider configuration. In order to do this, we need to extend Monolog's default
setup by adding our custom Processor and also extending the default Handler to
provide ours instead.

.. code-block:: php

    //Modify these use statements if necessary to adapt them to your namespace
    use Monolog\Formatter\TokenLineFormatter;
    use Monolog\Processor\TokenProcessor;

    $app->register(new Silex\Provider\MonologServiceProvider(), array(
        'monolog.logfile' => __DIR__.'/development.log',
    ));

    $app['monolog'] = $app->share($app->extend('monolog', function (Monolog\Logger $monolog) {
        $monolog->pushProcessor(new TokenProcessor());
        return $monolog;
    }));

    $app['monolog.handler'] = $app->share($app->extend('monolog.handler', function (Monolog\Handler\HandlerInterface $handler) {
        $handler->setFormatter(new TokenLineFormatter());
        return $handler;
    }));

Once all this is done, your logs will include a request hash like the following::

    [2016-01-01 00:00:00][5698bb8036da8] myapp.INFO: Matched route "POST_endpoint". {"route_parameters":{"_controller":"controller:endpoint","_route":"POST_endpoint"},"request_uri":"http://localhost:8000/endpoint"}
    [2016-01-01 00:00:01][5698bb8036da8] myapp.INFO: > POST /endpoint []
    [2016-01-01 00:00:02][5698bb8036da8] myapp.INFO: < 200 []
