<?php
//Include app data
//Change path if you need
include_once 'key.php';


//Twittwer
//Change path if you need
include_once 'twitteroauth.php';

session_start();

$state = $_SESSION['oauth_state'];
$session_token = $_SESSION['oauth_request_token'];
$oauth_token = $_REQUEST['oauth_token'];

if ($_REQUEST['oauth_token'] != NULL && $_SESSION['oauth_state'] === 'start') {
	$_SESSION['oauth_state'] = $state = 'returned';
}

$to = new TwitterOAuth($consumer_key, $consumer_secret);
$tok = $to->getRequestToken();

$_SESSION['oauth_request_token'] = $token = $tok['oauth_token'];
$_SESSION['oauth_request_token_secret'] = $tok['oauth_token_secret'];
$_SESSION['oauth_state'] = "start";

$request_link = $to->getAuthorizeURL($token,false);
$tw_login_url = '<a href="'.$request_link.'">Login</a>';


//facebook
//Change path if you need
include_once 'facebook.php';

$facebook = new Facebook(array(
'appId' => $app_id,
'secret' => $app_secret,
'cookie' => true
));

$fb_login_url = $facebook->getLoginUrl(array(
'scope' => 'publish_stream,offline_access'
));
?>

<!DOCTYPE HTML>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>Login | Google Reader Starred Entries</title>
</head>
<body>
<article>
<h1>Login</h1>

<section>
<h1>Twitter</h1>

<p><?php echo $tw_login_url; ?></p>
</section>

<section>
<h1>facebook</h1>

<p>
<?php
if($user){
echo '<a href="',$fb_logout_url,'">Logout</a>';

} else {
echo '<a href="',$fb_login_url,'">Login</a>';
}
?>
</p>
</section>
</article>
</body>
</html>
