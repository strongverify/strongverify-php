<?php

namespace StrongVerify\SDK;

use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\CoreException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class StrongVerify
{

    /**
     * Auth0 object
     *
     * @var object
     */
    private $auth0;

    /**
     * Auth0 JWT 
     *
     * @var string
     */
    private $auth0JWToken;

    /**
     * Guzzle HTTP Client
     *
     * @var object
     */
    protected $client;

    /**
     * StrongVerify URI
     *
     * @var object
     */
    protected $strongVerifyUri;

    /**
     * StrongVerify API Version
     *
     * @var object
     */
    protected $strongVerifyApiVersion;

    /**
     * Auth0 Domain, found in Application settings
     *
     * @var string
     */
    protected $auth0Domain;

    /**
     * Auth0 URI, derived from domain
     *
     * @var string
     */
    protected $auth0Uri;

    /**
     * Auth0 Client ID, found in Application settings
     *
     * @var string
     */
    protected $auth0ClientId;

    /**
     * Auth0 Client Secret, found in Application settings
     *
     * @var string
     */
    protected $auth0ClientSecret;

    /**
     * StrongVerify Constructor.
     */
    public function __construct(array $config)
    {

        // read in config and set as props
        $this->auth0Domain = $config['auth0_domain'] ?? getenv('AUTH0_DOMAIN') ?? null;
        if (empty($this->auth0Domain)) {
            throw new \Exception('Invalid AUTH0_DOMAIN');
        }
        $this->auth0Uri = 'https://'.$this->auth0Domain.'/';

        $this->auth0ClientId = $config['auth0_client_id'] ?? getenv('AUTH0_CLIENT_ID') ?? null;
        if (empty($this->auth0ClientId)) {
            throw new \Exception('Invalid AUTH0_CLIENT_ID');
        }

        $this->auth0ClientSecret = $config['auth0_client_secret'] ?? getenv('AUTH0_CLIENT_SECRET') ?? null;
        if (empty($this->auth0ClientSecret)) {
            throw new \Exception('Invalid AUTH0_CLIENT_SECRET');
        }

        $this->strongVerifyApiVersion='v1_0';

        $this->strongVerifyUri = $config['sv_api_base_uri'] ?? getenv('SV_API_BASE_URI') ?? null;
        if (empty($this->strongVerifyUri)) {
            throw new \Exception('Invalid SV_API_BASE_URI');
        }

        // the guzzle http client
        $this->client = new Client([]);

    }

    public function getVersion()
    {
        $result = $this->get('version');
        return $result;
    }

    public function getToken()
    {

        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->auth0ClientId,
            'client_secret' => $this->auth0ClientSecret,
            'audience' => 'https://strongverify.com/api',
        ];

        $result = $this->post($this->auth0Uri.'oauth/token', $params);
        
        if ($result->reasonPhrase != 'OK' || $result->statusCode != 200){

            throw new \Exception($result->reasonPhrase);

        }

        $this->auth0JWToken = $result->access_token;

        return $this->auth0JWToken;

    }

    public function getJwks()
    {
        $jwks_fetcher = new JWKFetcher();
        $jwks = $jwks_fetcher->getKeys($this->auth0Uri.'.well-known/jwks.json');
        return $jwks;
    }

    public function getOperation($operation){
        $operation = $this->strongVerifyUri.'/'.$this->strongVerifyApiVersion.'/'.$operation;
        return $operation;
    }

    public function createClient($params)
    {
        $operation = $this->strongVerifyUri.'/'.$this->strongVerifyApiVersion.'/client';
        $result = $this->post($operation, $params);
        
        if ($result->statusCode != 200){
            return $result;
        }

        return $result->id;
    }

    public function getClient($id, $nationalId=false)
    {
        $operation = $this->getOperation('client/'.$id);

        if ($nationalId){
            $operation .= '/nationalid';
        }

        $result = $this->get($operation);
        return $result;
    }

    public function deleteClient($id)
    {
        $operation = $this->getOperation('client/'.$id);
        $result = $this->delete($operation);
        
        if ($result->statusCode != 200){
            return $result;
        }

        return $result->message;
    }


    public function createAddress($id, $params)
    {
        $operation = $this->getOperation('client/'.$id.'/address');
        $result = $this->post($operation, $params);
        
        if ($result->statusCode != 200){
            return $result;
        }

        return $result->message;
    }

    public function updateAddress($id, $params)
    {
        $operation = $this->getOperation('client/'.$id.'/address');
        $result = $this->put($operation, $params);
        
        if ($result->statusCode != 200){
            return $result;
        }

        return $result->message;
    }

    public function getAddress($id)
    {
        $operation = $this->getOperation('client/'.$id.'/address');
        $result = $this->get($operation);
        return $result;
    }

    public function deleteAddress($id)
    {
        $operation = $this->getOperation('client/'.$id.'/address');
        $result = $this->delete($operation);
        
        if ($result->statusCode != 200){
            return $result;
        }

        return $result->message;
    }

    public function uploadDocument($id, $params)
    {

        if (!is_string($id)){
            throw new \Exception('You must include a client id');
        }

        $doctype = $params['doctype'] ?? null;
        if (empty($doctype)){
            throw new \Exception('You must include a doctype');
        }
        
        $filename = $params['filename'] ?? null;
        if (empty($filename)){
            throw new \Exception('You must include a local filename');
        }

        if (!file_exists($filename)){
            throw new \Exception('Cannot open local file '.$filename);
        }

        $body = fopen($filename, 'r');
        
        $operation = $this->getOperation('document/'.$id.'/'.$doctype);
        
        $result = $this->upload($operation, $body);

        if ($result->statusCode != 200){
            return $result;
        }

        return $result->reference;

    }

    public function downloadDocument($ref, $filename, $version='original')
    {
        if (empty($ref)){
            throw new \Exception('You must include a document reference');
        }

        $fp = fopen($filename, 'w');

        $operation = $this->getOperation("document/$ref/download?document=$version");
        $result = $this->download($operation, $fp);

        return $result;

    }

    public function getDocumentStatus($ref)
    {

        if (empty($ref)){
            throw new \Exception('You must include a document reference');
        }

        $operation = $this->getOperation('document/'.$ref.'/status');
        $result = $this->get($operation);
        
        if ($result->statusCode != 200){
            return $result;
        }

        return $result->state;
    }

    public function processDocument($ref){
       
        if (empty($ref)){
            throw new \Exception('You must include a document reference');
        }

        $operation = $this->getOperation('document/'.$ref.'/process');
        $result = $this->put($operation);
        
        if ($result->statusCode != 200){
            return $result;
        }

        return $result->state;
    }

    public function getDocumentInfo($ref, $task)
    {

        if (empty($ref)){
            throw new \Exception('You must include a document reference');
        }

        $operation = $this->getOperation('document/'.$ref.'/'.$task);
        $result = $this->get($operation);
        
        if ($result->statusCode != 200){
            return $result;
        }

        return $result;
    }

    /**
     * helper get function for guzzle client
     */
    private function get($operation)
    {
        $result = $this->request('get', $operation);
        return $result;
    }

    /**
     * helper post function for guzzle client
     */
    private function post($operation, $params = [])
    {
        if (isset($params) && !is_array($params)) {
            return false;
        }
        
        $result = $this->request('post', $operation, $params);
        return $result;
    }

    /**
     * helper patch function for guzzle client
     */
    private function put($operation, $params = [])
    {
        if (isset($params) && !is_array($params)) {
            return false;
        }

        $result = $this->request('put', $operation, $params);
        return $result;
    }

    /**
     * helper delete function for guzzle client
     */
    private function delete($operation)
    {
        $result = $this->request('delete', $operation);
        return $result;
    }

    /**
     * helper upload function for guzzle client
     */
    private function upload($operation, $body)
    {
        $result = $this->request('upload', $operation, array(
            'body' => $body
        ), array(
            'Content-Type' => 'application/octet-stream'
        ));
        
        return $result;
    }

    /**
     * helper download function for guzzle client
     */
    private function download($operation, $fp)
    {
        $stream = \GuzzleHttp\Psr7\stream_for($fp);

        $result = $this->request('download', $operation, array(
            'sink' => $stream
        ));
        
        return $result;
    }

    /**
     * handle Guzzle client requests
     */
    private function request($method, $operation, $params = [], $headers = [])
    {
        if (isset($params) && !is_array($params)) {
            return false;
        }
        
        if (isset($this->auth0JWToken) && is_string($this->auth0JWToken)){
            $headers['Authorization']='Bearer '.$this->auth0JWToken;
        }
        
        //echo $operation . "\n";

        try {
       
            if ($method == 'post'){
                
                $response = $this->client->post($operation, [
                    'json' => $params,
                    'headers' => $headers
                ]);

            }

            if ($method == 'get'){

                $response = $this->client->get($operation, [
                    'headers' => $headers
                ]);

            }

            if ($method == 'put'){

                $response = $this->client->put($operation, [
                    'json' => $params,
                    'headers' => $headers
                ]);

            }

            if ($method == 'delete'){

                $response = $this->client->delete($operation, [
                    'headers' => $headers
                ]);

            }

            if ($method == 'upload'){

                $response = $this->client->post($operation, [
                    'headers' => $headers,
                    'body'    => $params['body']
                ]);

            }

            if ($method == 'download'){

                $response = $this->client->get($operation, [
                    'headers' => $headers,
                    'sink'    => $params['sink']
                ]);

            }

        } catch (RequestException $e){
            
            if ($e->hasResponse()) {
                
                $response = $e->getResponse();
                
                return (object)array(
                    'reasonPhrase' => $response->getReasonPhrase(),
                    'statusCode' => $response->getStatusCode(),
                    'result' => json_decode($response->getBody()->getContents())
                );

            }

        } catch (ClientException $e){
            
            if ($e->hasResponse()) {
                
                $response = $e->getResponse();
                
                return (object)array(
                    'reasonPhrase' => $response->getReasonPhrase(),
                    'statusCode' => $response->getStatusCode(),
                    'result' => json_decode($response->getBody()->getContents())
                );

            }

        } catch (Exception $e){
            
            echo ('Unknown Exception');

        }
        
        $result = new \stdClass;
        $result->reasonPhrase = $response->getReasonPhrase();
        $result->statusCode = $response->getStatusCode();
        
        $contents = json_decode($response->getBody()->getContents());
        //var_dump(substr($operation, strrpos($operation, '/')));
        //if (substr($operation, strrpos($operation, '/')+1) == 'validation'){
            //var_dump($response->getBody()->getContents());
        //}
        if (!empty($contents)){

            foreach ($contents as $key => $value){
                $result->$key = $value;
            }   

        }   
        
        return $result;
    }

}