<?php
session_start();

include_once 'key.php';

$encoding = 'UTF-8';
ini_set('mbstring.internal_encoding', $encoding );
ini_set('mbstring.script_encoding', $encoding );
ini_set('default_charset', $encoding );


$timespan = 5 * 60;

$filename = 'latest';

$lastupdate = file_get_contents($filename);


//Login to Google
$url = 'https://www.google.com/accounts/ClientLogin';

$value = array(
	'service' => 'reader',
	'Email' => $email,
	'Passwd' => $password
);

$ch = curl_init();

curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_HEADER,0);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS, $value);

$response = curl_exec($ch);
$response = explode("\n",$response);

foreach($response as $key => $val){
	$response[$key] = explode('=',$val);
}

foreach($response as $key => $val){
	foreach($val as $key2 => $val2){
		if($response[$key][$key2] == 'SID'){
			$sid = $response[$key][$key2 + 1];
		}
		if($response[$key][$key2] == 'LSID'){
			$lsid = $response[$key][$key2 + 1];
		}
		if($response[$key][$key2] == 'Auth'){
			$auth = $response[$key][$key2 + 1];
		}
	}
}

curl_close($ch);


//Access to Google Reader
$ch = curl_init();
$url = 'http://www.google.com/reader/api/0/token';

$header = array('Authorization:GoogleLogin auth='.$auth);

curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_HEADER,0);
curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

$response = curl_exec($ch);
$tToken = substr($response,2);

curl_close($ch);

$ch = curl_init();
$url = 'https://www.google.com/reader/atom/user/-/state/com.google/starred?n=10';

$header = array(
	'Content-Type:application/x-www-form-urlencoded;charset=utf-8',
	'Authorization:GoogleLogin auth='.$auth
);

curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_HEADER,0);
curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

$response = curl_exec($ch);
$response = mb_convert_encoding($response,'UTF-8');

$old = "gr:crawl-timestamp-msec";
$new  = "timestamp";

$response = str_replace($old, $new, $response);

$xml = simplexml_load_string($response);

$entries = array();

foreach ($xml->entry as $it) {
	$timestamp = $it->attributes()->timestamp;

	if($timestamp > $lastupdate){
		$item = array();
		$item['timestamp'] = mb_convert_encoding($it->attributes()->timestamp,'UTF-8');
		$item['id'] = mb_convert_encoding($it->id,'UTF-8');
		if($it->category->attributes()->label == 'w'){
			$item['tag'] = mb_convert_encoding($it->category->attributes()->label,'UTF-8');
		}
		$item['title'] = mb_convert_encoding($it->title,'UTF-8');
		$item['link'] = mb_convert_encoding($it->link->attributes()->href,'UTF-8');
		$item['content'] = strip_tags(mb_convert_encoding($it->content,'UTF-8'));
		$item['updated'] = mb_convert_encoding($it->updated,'UTF-8');
		$entries[] = $item;
	} else {
		break;
	}
}

curl_close($ch);


//Login to Twitter
//Record token to file

//Change path if you need
include_once 'twitteroauth.php';

$accountfile = 'twitter';

$oauth_request_token = $_SESSION['oauth_request_token'];
$oauth_request_token_secret = $_SESSION['oauth_request_token_secret'];

$twitter = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_request_token, $oauth_request_token_secret);
$token = $twitter->getAccessToken();

$flag = 0;

if(file_exists($accountfile)){
	$accountdata = file($accountfile);

	foreach($accountdata as $key => $value){
		$accounts_tmp[] = unserialize($value);
	}

	foreach($accounts_tmp as $key => $value){
		if(is_array($value)){
			foreach($value as $key2 => $value2){
				if($key2 == 'user_id' && $value2 === $token['user_id']){
					$flag = 1;
				}
			}	
		} else {
			if($key == 'user_id' && $value === $token['user_id']){
				$flag = 1;
			}
		}
	}
}

if(array_key_exists('user_id',$token) && $flag === 0){
	file_put_contents($accountfile,serialize($token)."\n",FILE_APPEND);
}


//Login to facebook
//Record token to file

//Change path if you need
include_once 'facebook.php';

$tokenfile = 'facebook';

$token = $_SESSION['f_oauth_request_token'];

$flag = 0;

if(file_exists($tokenfile)){
	$tokendata = file($tokenfile);

	foreach($tokendata as $key => $value){
		$tokenes_tmp[] = unserialize($value);
	}

	foreach($tokenes_tmp as $key => $value){
		if(is_array($value)){
			foreach($value as $key2 => $value2){
				if($value2 === $token){
					$flag = 1;
				}
			}	
		} else {
			if($value === $token){
				$flag = 1;
			}
		}
	}
}

if($flag === 0){
	file_put_contents($tokenfile,serialize($token)."\n",FILE_APPEND);
}


//Tweet or Update entries
if(count($entries) !== 0){
	$handle = fopen($filename,'w');
	fwrite($handle,$entries[0]['timestamp']);
	fclose($handle);


	//Twitter
	$accountdata = file($accountfile);

	foreach($accountdata as $key => $value){
		$accounts[] = unserialize($value);
	}

	foreach($accounts as $key => $value){
		if(is_array($value)){
			foreach($value as $key2 => $value2){
				if($key2 == 'oauth_token'){
					$access_token = $value2;
				} else if($key2 == 'oauth_token_secret'){
					$access_token_secret = $value2;
				} else if($key2 == 'user_id'){
					$user_id = $value2;
				} else if($key2 == 'screen_name'){
					$screen_name = $value2;
				}
			}
		} else {
			if($key == 'oauth_token'){
				$access_token = $value;
			} else if($key == 'oauth_token_secret'){
				$access_token_secret = $value;
			} else if($key == 'user_id'){
				$user_id = $value;
			} else if($key == 'screen_name'){
				$screen_name = $value;
			}
		}

		for($i = 0 ; $i < count($entries) ; $i++){
			$to = new TwitterOAuth($consumer_key,$consumer_secret,$access_token,$access_token_secret);

			$req = $to->OAuthRequest(
				"https://twitter.com/statuses/update.xml",
				"POST",
				array("status" => $entries[$i]['title'].' [B!] '.$entries[$i]['link'])
			);

			$response = $req;
		}
	}


	//facebook
	$tokendata = file($tokenfile);

	foreach($tokendata as $key => $value){
		if($value !== null){
			$tokenes[] = unserialize($value);
		}
	}

	foreach($tokenes as $key => $value){

		$facebook = new Facebook(array(
			'appId' => $app_id,
			'secret' => $app_secret,
			'cookie' => true
		));

		$facebook->setAccessToken($value);

		$facebook->getUser();
		
		$user_profile = $facebook->api('/me');

		for($i = 0 ; $i < count($entries) ; $i++){
			$query = $facebook->api(
				'/me/feed','POST', array(
					'message' => $entries[$i]['title'],
					'link' => $entries[$i]['link'],
					'name' => $entries[$i]['title'],
					'caption' => $entries[$i]['link']
			));

			$response = $query;
		}
	}
	fclose($handle);
}

$_SESSION = array();
session_destroy();
?>
