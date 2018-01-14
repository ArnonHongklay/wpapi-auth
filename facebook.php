<?php

require_once __DIR__ . '/vendor/autoload.php'; // change path as needed

define('FACEBOOK_APP_ID', '601248543332595');
define('FACEBOOK_APP_SECRET', '9c8217c6f1bc53f4693b0ec430c592c2');

$fb = new \Facebook\Facebook([
  'app_id' => FACEBOOK_APP_ID,
  'app_secret' => FACEBOOK_APP_SECRET,
  'default_graph_version' => 'v2.10',
]);

// Use one of the helper classes to get a Facebook\Authentication\AccessToken entity.
//   $helper = $fb->getRedirectLoginHelper();
//   $helper = $fb->getJavaScriptHelper();
//   $helper = $fb->getCanvasHelper();
//   $helper = $fb->getPageTabHelper();

$accessToken = 'EAAIi1RXY2PMBAO0YXOJg2Lq0tfJzM3r4pgNY3uUJONTaqQ7RssVf4JrWBDJANhgKFpbfJD0BcnCdLQ92LJTUyI6hWRnrx33zhDBzWG6AXOXmQABj6cmUTa9Raj1p06aAQxVrRCFTvYD9eDQ75dNnh9oiZBEXfAww58QdkaUzdGcJ15urtPCZCFFQuHINYZD';

try {
    // Get the \Facebook\GraphNodes\GraphUser object for the current user.
    // If you provided a 'default_access_token', the '{access-token}' is optional.
    $response = $fb->get('/me', $accessToken);
} catch (\Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (\Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

$me = $response->getGraphUser();
echo 'Logged in as ' . $me->getName();
