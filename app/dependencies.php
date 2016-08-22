<?php
    // DIC configuration
    $container = $app->getContainer();

    // -----------------------------------------------------------------------------
    // Service providers
    // -----------------------------------------------------------------------------
    // Twig
    $container['view'] = function ($c) {
        $app_settings = $c->get('app');
        // First param is the "default language" to use.
        $translator = new Symfony\Component\Translation\Translator($app_settings['language'], new Symfony\Component\Translation\MessageSelector());
        // Set a fallback language incase you don't have a translation in the default language
        $translator->setFallbackLocales(['en_US']);
        // Add a loader that will get the php files we are going to store our translations in
        $translator->addLoader('php', new Symfony\Component\Translation\Loader\PhpFileLoader());
        // Add language files here
        $translator->addResource('php', '../app/lang/en_US.php', 'en_US'); // English
        $translator->addResource('php', '../app/lang/es_ES.php', 'es_ES'); // Spanish

        $settings = $c->get('settings');
        $view = new Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);
        // Add extensions
        $view->addExtension(new Slim\Views\TwigExtension($c->get('router'), $c->get('request')->getUri()));
        $view->addExtension(new Twig_Extension_Debug());

        // Add the parserextensions TwigExtension and TranslationExtension to the view
        $view->addExtension(new Symfony\Bridge\Twig\Extension\TranslationExtension($translator));

        $view->getEnvironment()->addGlobal('language', $app_settings['language']); // this
        $view->getEnvironment()->addGlobal('flash', $c['flash']); // this

        $loggedUser = $c['sentinel']->check();
        if ($loggedUser){
            $view->getEnvironment()->addGlobal("user", $loggedUser);

            $users = $c->sentinel->getUserRepository()->with('roles')->get()->whereIn('id', array($loggedUser->id));
       
            $rolesSlugs = [];
            foreach ($users as $key => $user) {
                foreach ($user['roles'] as $key => $role) {
                    $rolesSlugs[] = $role->slug;
                }
            }
            
            $roles = $c->sentinel->getRoleRepository()->createModel()->whereIn('slug', $rolesSlugs)->get();
            $permissions = [];

            foreach ($roles as $role) {
                $permissions = array_merge($role->permissions, $permissions);
            }
            $view->getEnvironment()->addGlobal("user_permissions", $permissions);
        }
        return $view;
    };
    // Flash messages
    $container['flash'] = function ($c) {
        return new Slim\Flash\Messages;
    };

    $container['csrf'] = function ($c) {
        $guard = new \Slim\Csrf\Guard();
        $guard->setFailureCallable(function ($request, $response, $next) {
            $request = $request->withAttribute("csrf_status", false);
            return $next($request, $response);
        });
        return $guard;
    };

    // Import the necessary classes fon Sentinel
    use Cartalyst\Sentinel\Native\Facades\Sentinel;

    // database
    use Illuminate\Database\Capsule\Manager as Capsule;
    $setting = include('settings.php');
    $capsule = new Capsule;
    $capsule->addConnection($setting['settings']['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $container['sentinel'] = function ($c) {
        $sentinel = new \Cartalyst\Sentinel\Native\Facades\Sentinel();
        $sentinel2 = $sentinel->getSentinel();
        return $sentinel2;
    };
    // -----------------------------------------------------------------------------
    // Service factories
    // -----------------------------------------------------------------------------
    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings');
        $logger = new Monolog\Logger($settings['logger']['name']);
        $logger->pushProcessor(new Monolog\Processor\UidProcessor());
        $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['logger']['path'], Monolog\Logger::DEBUG));
        return $logger;
    };
    