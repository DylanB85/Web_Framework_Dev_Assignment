<?php

namespace App;

use Silex\Application as SilexApplication;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class Application extends SilexApplication
{
    private $rootDir;
    private $env;

    public function __construct($env)
    {
        $this->rootDir = __DIR__.'/../../';
        $this->env = $env;

        parent::__construct();

        $app = $this;

        // Override these values in resources/config/prod.php file
        $app['var_dir'] = $this->rootDir.'/var';
        $app['locale'] = '';
        $app['http_cache.cache_dir'] = function (Application $app) {
            return $app['var_dir'].'/cache/http';
        };
        $app['monolog.options'] = [
            'monolog.logfile' => $app['var_dir'].'/logs/app.log',
            'monolog.name' => 'app',
            'monolog.level' => 300,
        ];
       // $app['security.users'] = array('Student'=> array('ROLE_STUDENT', 'student'));
       // $app['admin.users'] = array('Admin' => array('ROLE_ADMIN', 'admin'));

        $configFile = sprintf('%s/resources/config/%s.php', $this->rootDir, $env);
        if (!file_exists($configFile)) {
            throw new \RuntimeException(sprintf('The file "%s" does not exist.', $configFile));
        }
        require $configFile;

        $app->register(new DoctrineServiceProvider(),
            array('dbs.options' => array(
                'driver' => 'pdo_sqllite',
                'path' => __DIR__.'/app.db'
            ),
            ));
        $app->register(new FormServiceProvider());
        $app->register(new HttpCacheServiceProvider());
        $app->register(new HttpFragmentServiceProvider());
        $app->register(new ServiceControllerServiceProvider());
        $app->register(new TranslationServiceProvider());
        $app->register(new SessionServiceProvider());
        $app->register(new ValidatorServiceProvider());

        $app['app.param']=array(
            'db' => array(
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'dbname' => 'silex',
            )
        );


        $app->register(new SecurityServiceProvider(), array(
        'security.firewalls' => array(
            'admin' => array(
                'pattern' => '^/',
                'form' => array(
                    'login_path' => '/login',
                ),
                'logout' => true,
                'anonymous' => true,
                'users' => array(
                    'Student' =>array('ROLE_STUDENT', 'student'),
                    'lecturer'=>array('ROLE_LECTURER', 'lecturer'),
                    'Admin' => array('ROLE_ADMIN', 'admin'),
                )
            ),
        ),
    ));


        $app['security.default_encoder'] = function ($app) {
            return new PlaintextPasswordEncoder();
        };
        $app['security.utils'] = function ($app) {
            return new AuthenticationUtils($app['request_stack']);
        };

        $app->register(new TranslationServiceProvider());

        $app->register(new MonologServiceProvider(), $app['monolog.options']);

        $app->register(new TwigServiceProvider(), array(
            'twig.options' => array(
                'strict_variables' => false,
            ),
            'twig.form.templates' => array('bootstrap_3_horizontal_layout.html.twig'),
            'twig.path' => array($this->rootDir.'/resources/templates'),
        ));
        $app['twig'] = $app->extend('twig', function ($twig, $app) {
            $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
                $base = $app['request_stack']->getCurrentRequest()->getBasePath();

                return sprintf($base.'/'.$asset, ltrim($asset, '/'));
            }));

            return $twig;
        });

        if ('dev' === $this->env) {
            $app->register(new WebProfilerServiceProvider(), array(
                'profiler.cache_dir' => $app['var_dir'].'/cache/profiler',
                'profiler.mount_prefix' => '/_profiler', // this is the default
            ));
        }

        $app->mount('', new ControllerProvider());
    }



    public function getRootDir()
    {
        return $this->rootDir;
    }

    public function getEnv()
    {
        return $this->env;
    }
}
