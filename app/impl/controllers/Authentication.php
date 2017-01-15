<?php
/**
 * Authentication | Controller
 *
 * @version v0.0.1 (Dec. 21, 2016)
 * @copyright Copyright (c) 2016, Brevada
 */

namespace Brv\impl\controllers;

use Brv\core\routing\Controller;
use Brv\core\views\View;

use Brv\impl\middleware\Authentication as MiddleAuth;
use Brv\impl\entities\Account;

use Respect\Validation\Validator as v;

/**
 * The Authentication API.
 */
class Authentication extends Controller
{
    /**
     * Serves the plain login view.
     *
     * @api
     *
     * @throws \Respect\Validation\Exceptions\ValidationException on invalid input.
     * @throws \Brv\core\routing\ControllerException on failure.
     *
     * @param array $params URL parameters from the route pattern.
     * @return View
     */
    public function viewLogin(array $params)
    {
        /* Save the URL the user was attempting to reach. */
        \App::setState(\STATES::LOGIN_DEST, isset($params[2]) ? $params[2] : false);
        return new View('authentication/login');
    }

    /**
     * Logs out the currently logged in user.
     *
     * @api
     *
     * @throws \Respect\Validation\Exceptions\ValidationException on invalid input.
     * @throws \Brv\core\routing\ControllerException on failure.
     *
     * @param array $params URL parameters from the route pattern.
     */
    public function logout(array $params = [])
    {
        MiddleAuth::set(null);

        /* Remove all state data. */
        \App::clearState();

        \App::redirect('');
    }

    /**
     * Logins in a user by email and password.
     *
     * @api
     *
     * @throws \Respect\Validation\Exceptions\ValidationException on invalid input.
     * @throws \Brv\core\routing\ControllerException on failure.
     *
     * @param array $params URL parameters from the route pattern.
     * @return View
     */
    public function password(array $params)
    {
        $data = self::getBody();

        $email = self::from('email', $data);
        $password = self::from('password', $data);

        v::email()->check($email);
        // TODO: Enforce password rules.
        v::stringType()->min(1)->check($password);

        if ($email == null || $password == null) {
            self::fail("Invalid email and/or password.", \HTTP::BAD_PARAMS);
        }

        $account = Account::queryEmail($email);
        if ($account === null) {
            self::fail("Invalid email and/or password.", \HTTP::BAD_PARAMS);
        }

        if (password_verify($password, $account->getPassword())) {
            /* Deter session hijacking. */
            session_regenerate_id();
            MiddleAuth::set($account);
            return new View([
                "destination" => \App::getState(\STATES::LOGIN_DEST)
            ]);
        }

        // TODO: Track invalid password for this email.
        self::fail("Invalid email and/or password.", \HTTP::BAD_PARAMS);
    }
}
