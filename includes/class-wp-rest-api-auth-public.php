<?php

use \Firebase\JWT\JWT;

class wp_rest_api_auth_public
{
    private $plugin_name;
    private $version;
    private $namespace;
    private $jwt_error = null;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->namespace = $this->plugin_name . '/v' . intval($this->version);
    }

    public function add_api_routes()
    {
        register_rest_route($this->namespace, 'auth/sign_up', array(
          'methods' => 'POST',
          'callback' => array($this, 'sign_up'),
        ));

        register_rest_route($this->namespace, 'auth/sign_in', array(
          'methods' => 'POST',
          'callback' => array($this, 'sign_in'),
        ));

        register_rest_route($this->namespace, 'auth/facebook', array(
          'methods' => 'POST',
          'callback' => array($this, 'sign_in_via_facebook'),
        ));

        register_rest_route($this->namespace, 'auth/sign_out', array(
          'methods' => 'DELETE',
          'callback' => array($this, 'sign_out'),
        ));
    }

    public function sign_up($request)
    {
        if (!$this->json_web_token_available()) {
            return $this->error_message('jwt_auth_bad_config');
        }

        $username         = $request->get_param('username');
        $email            = $request->get_param('email');
        $password         = $request->get_param('password');
        $first_name       = $request->get_param('first_name');
        $last_name        = $request->get_param('last_name');
        $fb_id            = $request->get_param('facebook_id');
        $fb_access_token  = $request->get_param('access_token');

        if (isset($fb_access_token) && $this->facebook_app_available()) {
            $profile          = $this->get_facebook_profile($fb_access_token);
            $fb_id            = $profile['id'];

            $user = get_users(array(
                'meta_value' => $fb_id,
            ));

            if (isset($user)) {
                return $this->error_message('user_signup_failure');
            }
        }

        // https://codex.wordpress.org/Function_Reference/wp_insert_user
        $userdata = array(
          'user_login' => $username,
          'user_pass' => $password,
          'user_nicename' => $username,
          'user_email' => $email,
          'display_name' => $first_name . ' ' . $last_name,
          'first_name' => $first_name,
          'last_name' => $last_name
        );

        $user_id = wp_insert_user($userdata);
        add_user_meta($user_id, 'fb_id', $fb_id);
        add_user_meta($user_id, 'fb_access_token', $fb_access_token);

        if (!is_wp_error($user_id)) {
            return $this->response_message(get_user_meta($user_id), 201);
        } else {
            return $this->error_message('user_signup_failure');
        }
    }

    public function sign_in($request)
    {
        if (!$this->json_web_token_available()) {
            return $this->error_message('jwt_auth_bad_config');
        }

        $username = $request->get_param('username');
        $password = $request->get_param('password');

        if (isset($username) && isset($password)) {
            $user = wp_authenticate($username, $password);

            if (!is_wp_error($user)) {
                $token = $this->generate_token($user);
                return $this->response_message($token, 201);
            } else {
                $error_code = $user->get_error_code();
                $error_message = $user->get_error_message($error_code);
                return $this->message_error($error_code, $error_message);
            }
        } else {
            return $this->error_message('user_signin_failure');
        }
    }

    public function sign_in_via_facebook($request)
    {
        if (!$this->json_web_token_available()) {
            return $this->error_message('jwt_auth_bad_config');
        }

        if (!$this->facebook_app_available()) {
            return $this->error_message('facebook_auth_bad_config');
        }

        $access_token = $request->get_param('access_token');
        $profile = $this->get_facebook_profile($access_token);

        $user = get_users(array(
            'meta_value' => $profile['id'],
        ));

        if (isset($user)) {
            return $this->response_message($user, 201);
        } else {
            return $this->error_message('user_signin_via_facebook_failure');
        }
    }

    public function sign_in_via_twitter($request)
    {
    }
    public function sign_in_via_instagram($request)
    {
    }
    public function sign_in_via_google($request)
    {
    }
    public function sign_in_via_github($request)
    {
    }

    public function sign_out($request)
    {
        wp_logout();
    }

    private function get_facebook_profile($access_token)
    {
        $fb = new \Facebook\Facebook([
          'app_id' => FACEBOOK_APP_ID,
          'app_secret' => FACEBOOK_APP_SECRET,
          'default_graph_version' => 'v2.10',
        ]);

        try {
            $response = $fb->get('/me?fields=id,name', $access_token);
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            return 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            return 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $user = $response->getGraphUser();
        return $user;
    }

    private function json_web_token_available()
    {
        return defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
    }

    private function facebook_app_available()
    {
        $facebook_app_id      = defined('FACEBOOK_APP_ID') ? FACEBOOK_APP_ID : false;
        $facebook_app_secret  = defined('FACEBOOK_APP_SECRET') ? FACEBOOK_APP_SECRET : false;

        return $facebook_app_id && $facebook_app_secret;
    }

    private function response_message($message, $status = 200)
    {
        return array(
          // 'code' => 'jwt_auth_valid_token',
          'code' => 'ok',
          'message' => $message,
          'data' => array(
            'status' => $status,
          ),
        );
    }

    private function error_message($code, $message = null)
    {
        switch ($code) {
          case 'jwt_auth_bad_config':
            return new WP_Error(
              $code,
              __('JWT is not configurated properly, please contact the admin', 'wp-api-jwt-auth'),
              array(
                'status' => 403,
              )
            );
            break;
          case 'facebook_auth_bad_config':
            return new WP_Error(
              $code,
              __('Facebook is not configurated properly', 'wp-api-facebook-auth'),
              array(
                'status' => 403,
              )
            );
            break;
          case 'user_signup_failure':
            return new WP_Error(
              $code,
              __('User signup failure', 'wp-api-user-signup'),
              array(
                'status' => 403,
              )
            );
            break;
          case 'user_signin_failure':
            return new WP_Error(
              $code,
              __('User signin failure', 'wp-api-user-signin'),
              array(
                'status' => 403,
              )
            );
            break;
          case 'user_signin_via_facebook_failure':
            return new WP_Error(
              $code,
              __('User signin via facebook failure', 'wp-api-user-signin-via-facebook'),
              array(
                'status' => 403,
              )
            );
            break;
          default:
            return new WP_Error(
              $code,
              $message,
              array(
                'status' => 403,
              )
            );
            break;
        }
    }

    private function generate_token($user)
    {
        $issuedAt = time();
        $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
        $expire = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);

        $token = array(
          'iss' => get_bloginfo('url'),
          'iat' => $issuedAt,
          'nbf' => $notBefore,
          'exp' => $expire,
          'data' => array(
            'user' => array(
              'id' => $user->data->ID,
            ),
          ),
        );

        $token = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token, $user), JWT_AUTH_SECRET_KEY);

        $data = array(
          'token' => $token,
          'user_email' => $user->data->user_email,
          'user_nicename' => $user->data->user_nicename,
          'user_display_name' => $user->data->display_name,
        );

        return apply_filters('jwt_auth_token_before_dispatch', $data, $user);
    }

    public function validate_token($output = true)
    {
        $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ?  $_SERVER['HTTP_AUTHORIZATION'] : false;

        if (!$auth) {
            $auth = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ?  $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
        }

        if (!$auth) {
            $this->error_message('jwt_auth_no_auth_header', 'Authorization header not found.');
        }

        list($token) = sscanf($auth, 'Bearer %s');
        if (!$token) {
            $this->error_message('jwt_auth_bad_auth_header', 'Authorization header malformed.');
        }

        $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
        if (!$secret_key) {
            $this->error_message('jwt_auth_bad_config', 'JWT is not configurated properly, please contact the admin');
        }

        try {
            $token = JWT::decode($token, $secret_key, array('HS256'));
            if ($token->iss != get_bloginfo('url')) {
                $this->error_message('jwt_auth_bad_iss', 'The iss do not match with this server');
            }

            if (!isset($token->data->user->id)) {
                $this->error_message('jwt_auth_bad_request', 'User ID not found in the token');
            }

            if (!$output) {
                return $token;
            }

            return array(
              'code' => 'jwt_auth_valid_token',
              'data' => array(
                'status' => 200,
              ),
            );
        } catch (Exception $e) {
            $this->error_message('jwt_auth_invalid_token', $e->getMessage());
        }
    }

    public function determine_current_user($user)
    {
        $rest_api_slug = rest_get_url_prefix();
        $valid_api_uri = strpos($_SERVER['REQUEST_URI'], $rest_api_slug);

        if (!$valid_api_uri) {
            return $user;
        }

        $validate_uri = strpos($_SERVER['REQUEST_URI'], 'token/validate');
        if ($validate_uri > 0) {
            return $user;
        }

        $token = $this->validate_token(false);

        if (is_wp_error($token)) {
            if ($token->get_error_code() != 'jwt_auth_no_auth_header') {
                $this->jwt_error = $token;
                return $user;
            } else {
                return $user;
            }
        }
        return $token->data->user->id;
    }

    public function add_cors_support()
    {
        $enable_cors = defined('JWT_AUTH_CORS_ENABLE') ? JWT_AUTH_CORS_ENABLE : false;
        if ($enable_cors) {
            $headers = apply_filters('jwt_auth_cors_allow_headers', 'Access-Control-Allow-Headers, Content-Type, Authorization');
            header(sprintf('Access-Control-Allow-Headers: %s', $headers));
        }
    }

    public function rest_pre_dispatch($request)
    {
        if (is_wp_error($this->jwt_error)) {
            return $this->jwt_error;
        }
        return $request;
    }
}
