<?php
	// Routes
	/*$app->get('/', App\Action\HomeAction::class)
	    ->setName('homepage');*/

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	$app->get('/', function(Request $request, Response $response, $args) {
		$loggedUser = $this['sentinel']->check();
		if (!$loggedUser) {
        	return $response->withStatus(302)->withHeader('Location', '/login');
	    }

		return $this->view->render($response, 'dashboard.twig', []);
		//return $this->router->pathFor('hello');		
	});

	$app->get('/login', function(Request $request, Response $response, $args) {
		$loggedUser = $this['sentinel']->check();
		if ($loggedUser) {
        	return $response->withStatus(302)->withHeader('Location', '/');
	    }
		return $this->view->render($response, 'login.twig');
	});

	$app->post('/login', function (Request $request, Response $response, $args) {
    	$data = $request->getParsedBody();
    	$user_data = [];
    	$remember = isset($data['remember']) && $data['remember'] == 'on' ? true : false;

    	try {
        	if (!$this['sentinel']->authenticate([
                	'email' => filter_var($data['email'], FILTER_VALIDATE_EMAIL, FILTER_SANITIZE_EMAIL),
                	'password' => filter_var($data['password']),
            	], $remember)) {

            	echo 'Invalid email or password.';

            	return;
        	} 
        	else {
            	return $response->withStatus(302)->withHeader('Location', '/');
            	return;
        	}
    	} catch (Cartalyst\Sentinel\Checkpoints\ThrottlingException $ex) {
        	echo "Too many attempts!";
        	return;
    	} catch (Cartalyst\Sentinel\Checkpoints\NotActivatedException $ex){
        	echo "Please activate your account before trying to log in";
	        return;
    	}
	});

	$app->get('/logout', function (Request $request, Response $response, $args) {
		$loggedUser = $this['sentinel']->check();
		if (!$loggedUser) {
        	return $response->withStatus(302)->withHeader('Location', '/login');
	    }

	    $this['sentinel']->logout();
	    $this->flash->addMessage('global', 'This is a message');
	    // 'Logged out successfully.';
	    return $response->withStatus(302)->withHeader('Location', '/login');
	});

	$app->get('/signup', function(Request $request, Response $response, $args) {
		$loggedUser = $this['sentinel']->check();
		if ($loggedUser) {
        	return $response->withStatus(302)->withHeader('Location', '/');
	    }
		return $this->view->render($response, 'signup.twig');
	});

	$app->post('/signup', function (Request $request, Response $response, $args) {
    	// we leave validation for another time
    	$data = $request->getParsedBody();
    	$user_data = [];
    	$user_data['firstname'] = filter_var($data['firstname'], FILTER_SANITIZE_STRING);
    	$user_data['lastname'] = filter_var($data['lastname'], FILTER_SANITIZE_STRING);
    	$user_data['email'] = filter_var($data['email'], FILTER_VALIDATE_EMAIL, FILTER_SANITIZE_EMAIL);
    	$user_data['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING);
    	$user_data['password_confirmation'] = filter_var($data['password_confirmation'], FILTER_SANITIZE_STRING);

    	$role = $this['sentinel']->findRoleByName('User');

    	if ($this['sentinel']->findByCredentials([
        	'login' => $user_data['email'],
    	])) {
        	echo 'User already exists with this email.';

        	return;
    	}

    	if ($user_data['password'] === $user_data['password_confirmation']){
		    $user = $this['sentinel']->create([
		        'first_name' => $user_data['firstname'],
		        'last_name' => $user_data['lastname'],
		        'email' => $user_data['email'],
		        'password' => $user_data['password']
		    ]);

		    // attach the user to the role
    		$role->users()->attach($user);

    		// create a new activation for the registered user
		    $activation = (new Cartalyst\Sentinel\Activations\IlluminateActivationRepository)->create($user);

		    // Set flash message for next request
		    $this->flash->addMessage('global', 'Please check your email to complete your account registration.');

		    // Redirect
		    return $response->withStatus(302)->withHeader('Location', '/');
		    //echo "Please check your email to complete your account registration.";
		}
		else{
			// Set flash message for next request
		    $this->flash->addMessage('Test', 'This is a message');

		    // Redirect
		    //return $res->withStatus(302)->withHeader('Location', '/signup');
		}	
	});

	$app->get('/user/activate', function (Request $request, Response $response, $args) {
	    
	    $data = $request->getQueryParams();
    	$code = filter_var($data['code']);

    	$activationRepository = new Cartalyst\Sentinel\Activations\IlluminateActivationRepository;
    	$activation = Cartalyst\Sentinel\Activations\EloquentActivation::where("code", $code)->first();

    	if (!$activation){
        	echo "Activation error!";
        	return;
    	}

    	$user = $this['sentinel']->findById($activation->user_id);

    	if (!$user){
        	echo "User not found!";
        	return;
    	}

    	if (!$activationRepository->complete($user, $code)){
        	if ($activationRepository->completed($user)){
            	echo 'User is already activated. Try to log in.';
            	return;
       		}

        	echo "Activation error!";
        	return;
    	}

    	echo 'Your account has been activated. Log in to your account.';

    	return;
	});

	$app->get('/users', function(Request $request, Response $response, $args) {
		$loggedUser = $this['sentinel']->check();
		if (!$loggedUser) {
        	return $response->withStatus(302)->withHeader('Location', '/login');
	    }
		if ($loggedUser->hasAccess('user.*')){
			$users = $this->sentinel->getUserRepository()->get();
		    return $this->view->render($response, 'users.twig', ['users' => $users]);
		}
		else{
		    return $response->withStatus(302)->withHeader('Location', '/');
		}
	});

	$app->get('/error', function(Request $request, Response $response, $args) {

	    return $response->withStatus(302)->withHeader('Location', '/error/0');
	});

	$app->get('/error/{monolog}', function(Request $request, Response $response, $args) {
		if ($args['monolog'] === '1'){
			$path = $this->get('settings')['logger'];
        	@$file = file($path['path']);
		}
		else{
			$path = $this->get('app')['error_log'];
        	@$file = file($path);
		}
        
        if (!$file) {
            $messages = ["No error log found."];
        } else {
            $messages = array_reverse($file);
        }
        echo "<pre>";
        echo implode("<br>",$messages);
        echo "</pre>";
	});
	