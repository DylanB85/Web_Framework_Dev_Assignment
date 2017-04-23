<?php
namespace App;
use Silex\Api\ControllerProviderInterface;
use Silex\Application as App;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\FormPass;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
class ControllerProvider implements ControllerProviderInterface
{
    private $app;
    public function connect(App $app)
    {
        $this->app = $app;
        $app->error([$this, 'error']);
        $controllers = $app['controllers_factory'];
        $controllers
            ->get('/', [$this, 'homepage'])
            ->bind('homepage');
        $controllers
            ->get('/login', [$this, 'login'])
            ->bind('login');
        $controllers
            ->get('/doctrine', [$this, 'doctrine'])
            ->bind('doctrine');
        $controllers
            ->match('/form', [$this, 'form'])
            ->bind('form');
        $controllers
            ->get('/cache', [$this, 'cache'])
            ->bind('cache');
        return $controllers;
    }
    public function homepage(App $app)
    {
        $app['session']->getFlashBag()->add('lecturer1', 'Lecturer 1 Bibliography');
        $app['session']->getFlashBag()->add('lecturer2', 'Lecturer 2 Bibliography');
        $app['session']->getFlashBag()->add('lecturer3', 'Lecturer 3 Bibliography');
        $app['session']->getFlashBag()->add('lecturer4', 'Lecturer 4 Bibliography');
        return $app['twig']->render('index.html.twig');
    }

    public function login(App $app)
    {
        return $app['twig']->render('login.html.twig', array(
            'error' => $app['security.utils']->getLastAuthenticationError(),
            'username' => $app['security.utils']->getLastUsername(),
        ));
    }
    public function doctrine(App $app)
    {
        return $app['twig']->render('doctrine.html.twig', array(
            'posts' => $app['db']->fetchAll('SELECT * FROM post'),
        ));
    }

    public function form(App $app, Request $request)
    {
        $builder = $app['form.factory']->createBuilder(Type\FormType::class);
        $choices = array('magazine', 'book', 'website', 'newspaper');
        $form = $builder
            ->add('name', Type\TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'attr' => array('placeholder' => 'What is your name' )
            ))
            ->add('suggested_title', Type\TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'attr' => array('placeholder' => 'not blank constraints'),
            ))
            ->add('Why_have_you_suggested_this', Type\TextareaType::class)
            ->add('email', Type\EmailType::class)
            ->add('website_url', Type\UrlType::class)
            ->add('choice1', Type\ChoiceType::class, array(
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
            ))
            ->add('source_date', Type\DateType::class)
            ->add('time_of_suggestion', Type\TimeType::class)
            ->add('email', Type\EmailType::class)
            ->add('submit', Type\SubmitType::class)
            ->getForm()
        ;
        if ($form->handleRequest($request)->isSubmitted()) {
            if ($form->isValid()) {
                $app['session']->add('This form is valid');
            } else {
                $form->addError(new FormError('This is not valid'));
                $app['session']->add('This form is not valid');
            }
        }
        return $app['twig']->render('form.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    public function cache(App $app)
    {
        $response = new Response($app['twig']->render('cache.html.twig', array('date' => date('Y-M-d h:i:s'))));
        $response->setTtl(10);
        return $response;
    }
    public function error(\Exception $e, Request $request, $code)
    {
        if ($this->app['debug']) {
            return;
        }
        switch ($code) {
            case 404:
                $message = 'The requested page could not be found.';
                break;
            default:
                $message = 'We are sorry, but something went terribly wrong.';
        }
        return new Response($message, $code);
    }
}