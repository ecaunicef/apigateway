<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Cookie\SetCookie;

trait ConsumeExternalService
{
    /**
     * Send request to any service
     * @param $method
     * @param $requestUrl
     * @param array $formParams
     * @param array $headers
     * @return string
     */
    public function performRequest($method, $requestUrl, $formParams = [], $headers = [],$request)
    {

        // Create a new instance of CookieJar
        $cookieJar = new CookieJar();

        // Create a new Symfony Request object
        //$request = Request::createFromGlobals();

        // Get the cookies from the incoming request
        $cookies = $request->cookies->all();

        // Set the cookies in the Guzzle CookieJar
        $cookieJar->fromArray($cookies, $request->getHost());

        // Get the cookies from the browser's request headers
        $browserCookies = $_SERVER['HTTP_COOKIE'] ?? '';
        

        /** Forward cookie to Services */
        $splitURL = parse_url($requestUrl);
        
        // Set the domain and port information for the cookies
        $domain = @$splitURL['host'];
        $port = @$splitURL['port'];
        
        // Parse the cookies and add them to the CookieJar
        $cookies = explode(';', $browserCookies);
        foreach ($cookies as $cookie) {
            $cookieParts = explode('=', trim($cookie));
            $cookieName = $cookieParts[0];
            $cookieValue = isset($cookieParts[1]) ? $cookieParts[1] : '';

            $cookieJar->setCookie(new SetCookie([
                'Name' => $cookieName,
                'Value' => $cookieValue,
                'Domain' => $domain,
                'Path' => '/',
                'Port' => $port
            ]));
        }



        $client = new Client([
            'base_uri'  =>  $this->baseUri,
            'cookies' => $cookieJar
        ]);
        
        $forwardHeaders = []; 
        if(isset($headers['authorization']))
        {   
            $forwardHeaders['authorization'] = $headers['authorization'];
        }

        if(isset($headers['locale']))
        {   
            $forwardHeaders['locale'] = $headers['locale'];
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $clientIP = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $clientIP = $_SERVER['REMOTE_ADDR'];
        }

        $forwardHeaders['origin_ip'] = $clientIP;
        try {


            $userRequest = [
                'headers'     => $forwardHeaders,
                'cookies' => $cookieJar
            ];
            
            $filedata = [];
            $body = [];

            if($_FILES){//image handling
           
                foreach( $_FILES as $key => $file){   

                    if(!($file['tmp_name'] == '')){
                        $file['filename'] = $file['name'];
                        $file['name']=$key;
                        $file['contents'] = fopen($file['tmp_name'], 'r');
                        $file['headers'] = array('Content-Type' => mime_content_type($file['tmp_name']));
                    }
                    else{
                        $file['contents'] = '';
                    }
                    array_push($filedata, $file);

                }
                $body['multipart'] = $filedata;

                foreach ($formParams as $key => $row) {
                    $body['multipart'][] = ['name' => $key, 'contents' => $row ];
                }

                $userRequest['multipart'] = $body['multipart'];


            } else {
                $userRequest['form_params'] = $formParams;

            }


            $response = $client->request($method, $requestUrl, $userRequest);

            // $response = $client->request($method, $requestUrl, [
            //     'form_params' => $formParams,
            //     'headers'     => $forwardHeaders,
            //     'cookies' => $cookieJar
                
            //     //'headers'     => [],
            // ]);
            

            /** Received Cookie from Servcie and Set to browser**/
            // Get the cookies from the cookie jar
            
            if (str_contains($requestUrl, 'login')) { 
                $cookies = $cookieJar->toArray();
            } else {
                $cookies = [];
            }
            // Create a new response object
            $newResponse = new \GuzzleHttp\Psr7\Response();

            if(count($cookies)) {
                // Iterate over the cookies and add them to the response headers
                foreach ($cookies as $cookie) {
                    $name = $cookie['Name'];
                    $value = $cookie['Value'];
                    $domain = $request->getHost();
                    $path = $cookie['Path'];
                    $expires = isset($cookie['Expires']) ? gmdate('D, d M Y H:i:s T', $cookie['Expires']) : null;
                    
                    // $secure = isset($cookie['Secure']) ? $cookie['Secure'] : false;
                    $secure = (str_contains($_SERVER['HTTP_REFERER'],'https')) ? true : false;

                    //$httpOnly = isset($cookie['HttpOnly']) ? $cookie['HttpOnly'] : false;
        
                    $httpOnly = (str_contains($_SERVER['HTTP_REFERER'],'https'))? true : false;
        

                    $cookieHeader = "{$name}={$value}; Domain={$domain}; Path={$path}";
                    if ($expires) {
                        $cookieHeader .= "; Expires={$expires}";
                    }
                    if ($secure) {
                        $cookieHeader .= "; Secure=true";
                    }
                    if ($httpOnly) {
                        $cookieHeader .= "; HttpOnly=true";
                    }

                    $newResponse = $newResponse->withAddedHeader('Set-Cookie', $cookieHeader);  

                    
                    // setcookie($name, $value, $expires, $path, $domain, true, true);

                }
            } else {

                if (str_contains($requestUrl, 'logout')) { 
                                   
                    $time = time() - 3600; // Set the expiration time in the past
                    setcookie('auth', '',$time, '/',$request->getHost());

                    $deletedCookie = new SetCookie([
                        'Name' => 'auth',
                        'Value' => '',
                        'Domain' => $request->getHost(),
                        'Path' => '/',
                        'Port' => $port,
                        'Expires' => $time
                    ]);
                    
                    // Add the deleted cookie to the CookieJar
                    $cookieJar->setCookie($deletedCookie);
                }
                
            }

            //$cookieHeader .= "; samesite=false";

            // Add the cookie to the response headers
            

            // Set the response body and any other necessary headers
            $newResponse = $newResponse->withBody($response->getBody());
            $newResponse = $newResponse->withHeader('Content-Type', 'text/html');
            // Return the response to the browser
            return $newResponse;

            //return $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {

             $errData = json_decode($exception->getResponse()->getBody()->getContents(), true);
             return response()->json([
                'status'    => 0,
                'message'   => @$errData['message'],
                //'errors'    => $errData
            ],$exception->getResponse()->getStatusCode());
            
        }
    }
}
