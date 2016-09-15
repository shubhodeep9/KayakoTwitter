<?php
define(CONSUMER_SECRET, 'V2VhVJWLSxZbdhDLLyQN1eRy7PMW0ooqgFFynjTkYa8a4B64GF');
define(CONSUMER_KEY, 'klbbx9xjYm6tJxzUhutcmHSF2');
require "twitteroauth/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;

$access_token = "482107777-irsRqZBYtDXeQ4umRkL6lQsF07aMe1YDT3HrUdug";
$access_token_secret = "5LGWjY0M8e3g7Z7SH9ceXHUgkPhMomNELhFlev2MXM6Oc";
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token, $access_token_secret);

$statuses = $connection->get("statuses/home_timeline", ["count" => 25, "exclude_replies" => true]);

?>