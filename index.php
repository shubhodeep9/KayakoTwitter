<?php
class KayakoTwitter
{
    /**
     * @var Class members required for GET request and response
     */
    private $oauth_access_token;
    private $oauth_access_token_secret;
    private $consumer_key;
    private $consumer_secret;
    private $getfield;
    protected $oauth;
    public $url;
    public $requestMethod;
    protected $httpStatusCode;

    /**
     * Creates KayakoTwitter instance
     * @throws \RuntimeException When cURL isn't installed
     * @throws \InvalidArgumentException When incomplete settings parameters are provided
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        if (!function_exists('curl_init'))
        {
            throw new RuntimeException('KayakoTwitter requires cURL extension to be installed, can not fetch desired request ');
        }

        if (!isset($settings['oauth_access_token']) || !isset($settings['oauth_access_token_secret']) || !isset($settings['consumer_key']) || !isset($settings['consumer_secret']))
        {
            throw new InvalidArgumentException('Few arguments are missing');
        }
        $this->oauth_access_token = $settings['oauth_access_token'];
        $this->oauth_access_token_secret = $settings['oauth_access_token_secret'];
        $this->consumer_key = $settings['consumer_key'];
        $this->consumer_secret = $settings['consumer_secret'];
    }

    /**
     * Set getfield string
     *
     * @param string $string Get key and value pairs as string
     *
     * @throws \Exception
     *
     * @return \KayakoTwitter Instance of self for method chaining
     */
    public function setGetfield($string)
    {
        $getfields = preg_replace('/^\?/', '', explode('&', $string));
        $params = array();
        foreach ($getfields as $field)
        {
            if ($field !== '')
            {
                list($key, $value) = explode('=', $field);
                $params[$key] = $value;
            }
        }
        $this->getfield = '?' . http_build_query($params);

        return $this;
    }

    /**
     * Get getfield string (simple getter)
     *
     * @return string $this->getfields
     */
    public function getGetfield()
    {
        return $this->getfield;
    }
    /**
     * Build the Oauth object using params set in construct and additionals
     * passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
     *
     * @param string $url           The API url to use. Example: https://api.twitter.com/1.1/search/tweets.json
     * @param string $requestMethod Either POST or GET
     *
     * @throws \Exception
     *
     * @return \KayakoTwitter Instance of self for method chaining
     */
    public function buildOauth($url, $requestMethod)
    {
        if (strtolower($requestMethod)!='get')
        {
            throw new Exception('Request method must be either POST or GET');
        }

        $consumer_key              = $this->consumer_key;
        $consumer_secret           = $this->consumer_secret;
        $oauth_access_token        = $this->oauth_access_token;
        $oauth_access_token_secret = $this->oauth_access_token_secret;

        $oauth = array(
            'oauth_consumer_key' => $consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $oauth_access_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );

        $getfield = $this->getGetfield();

        if (!is_null($getfield))
        {
            $getfields = str_replace('?', '', explode('&', $getfield));

            foreach ($getfields as $g)
            {
                $split = explode('=', $g);

                /** In case a null is passed through **/
                if (isset($split[1]))
                {
                    $oauth[$split[0]] = urldecode($split[1]);
                }
            }
        }

        $base_info = $this->buildBaseString($url, $requestMethod, $oauth);
        $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;

        $this->url = $url;
        $this->requestMethod = $requestMethod;
        $this->oauth = $oauth;

        return $this;
    }


    /**
     * Private method to generate the base string used by cURL
     *
     * @param string $baseURI
     * @param string $method
     * @param array  $params
     *
     * @return string Built base string
     */
    private function buildBaseString($baseURI, $method, $params)
    {
        $return = array();
        ksort($params);

        foreach($params as $key => $value)
        {
            $return[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $return));
    }


    /**
     * Private method to generate authorization header used by cURL
     *
     * @param array $oauth Array of oauth data generated by buildOauth()
     *
     * @return string $return Header used by cURL for request
     */
    private function buildAuthorizationHeader(array $oauth)
    {
        $return = 'Authorization: OAuth ';
        $values = array();

        foreach($oauth as $key => $value)
        {
            if (in_array($key, array('oauth_consumer_key', 'oauth_nonce', 'oauth_signature',
                'oauth_signature_method', 'oauth_timestamp', 'oauth_token', 'oauth_version'))) {
                $values[] = "$key=\"" . rawurlencode($value) . "\"";
            }
        }

        $return .= implode(', ', $values);
        return $return;
    }

    /**
     * Perform the actual data retrieval from the API
     *
     * @param boolean $return      If true, returns data. This is left in for backward compatibility reasons
     * @param array   $curlOptions Additional Curl options for this request
     *
     * @throws \Exception
     *
     * @return string json If $return param is true, returns json data.
     */
    public function performRequest($return = true, $curlOptions = array())
    {
        if (!is_bool($return))
        {
            throw new Exception('performRequest parameter must be true or false');
        }

        $header =  array($this->buildAuthorizationHeader($this->oauth), 'Expect:');

        $getfield = $this->getGetfield();

        $options = array(
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ) + $curlOptions;

            if ($getfield !== '')
            {
                $options[CURLOPT_URL] .= $getfield;
            }

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);

        $this->httpStatusCode = curl_getinfo($feed, CURLINFO_HTTP_CODE);

        if (($error = curl_error($feed)) !== '')
        {
            curl_close($feed);

            throw new \Exception($error);
        }

        curl_close($feed);

        return $json;
    }

    /**
     * Get the HTTP status code for the previous request
     *
     * @return integer
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}

/**
*The main program block
*This block will use the above class
*and then filter out the response
*on basis of atleast 1 retweet
*/
header('Content-Type: application/json');
$CONSUMER_SECRET = $_ENV["CONSUMER_SECRET"];
$CONSUMER_KEY = $_ENV["CONSUMER_KEY"];


$access_token = $_ENV["ACCESS_TOKEN"];
$access_token_secret = $_ENV["ACCESS_SECRET"];
$settings = array(
    'oauth_access_token' => $access_token,
    'oauth_access_token_secret' => $access_token_secret,
    'consumer_key' => $CONSUMER_KEY,
    'consumer_secret' => $CONSUMER_SECRET
);

$url = 'https://api.twitter.com/1.1/search/tweets.json';
$getfield = '?q=%23custserv';
$requestMethod = 'GET';
$twitter = new KayakoTwitter($settings);
$apiresponse = $twitter->setGetfield($getfield)
             ->buildOauth($url, $requestMethod)
             ->performRequest();
$apiresponse = json_decode($apiresponse,true);
$output = array('Author'=>'Shubhodeep Mukherjee','Attribute'=>'Output with tweets having #custserv and minimum 1 retweet_count','Response'=>array());
foreach ($apiresponse['statuses'] as $key => $value) {
    if($value["retweet_count"]>0){
        array_push($output['Response'],$value);
    }
}

echo json_encode($output);
/**
*End of block
*/