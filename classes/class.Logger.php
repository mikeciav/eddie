<?php
//Logger logs a buy or sell transaction in a specified csv file
//It can also tweet out the transaction

require_once "private/defines.php";
require_once "vendor/TwitterAPIExchange.php";

class Logger{

	public function __construct($path="log/log.csv"){
		$this->path = $path;

		/** Set access tokens here - see: https://dev.twitter.com/apps/ **/
		$settings = array(
		    'oauth_access_token' => TWITTER_OAUTH_ACCESS_TOKEN,
		    'oauth_access_token_secret' => TWITTER_OAUTH_ACCESS_TOKEN_SECRET,
		    'consumer_key' => TWITTER_CONSUMER_KEY,
		    'consumer_secret' => TWITTER_CONSUMER_SECRET
		);

		$this->twitter = new TwitterAPIExchange($settings);
	}
	
	public function logTransaction($side, $size, $price){
		$this->tweetTransaction($side);
		$total = ($side == "buy") ? $size / $price : $size * $price;
		$string = $side . "," . $size . "," . $price . "," . $total;
		return file_put_contents($this->path, $string.PHP_EOL , FILE_APPEND | LOCK_EX);
	}

	private function tweetTransaction($side){
		$message = $side == "buy" ? "I'm going long on ETH/USD!" : "I'm going short on ETH/USD.";

		/** URL for REST request, see: https://dev.twitter.com/docs/api/1.1/ **/
		$url = 'https://api.twitter.com/1.1/statuses/update.json';
		$requestMethod = 'POST';

		/** POST fields required by the URL above. See relevant docs as above **/
		$postfields = array(
			'id' => TWITTER_USERNAME,
		    'status' => $message
		);

		/** Perform a POST request and echo the response **/
		
		echo $this->twitter->buildOauth($url, $requestMethod)
		             ->setPostfields($postfields)
		             ->performRequest();
	}

}
?>