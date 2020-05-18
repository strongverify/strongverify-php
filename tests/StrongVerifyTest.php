<?php 
namespace StrongVerify\Tests;

//use Faker;
use PHPUnit\Framework\TestCase;
use StrongVerify\SDK\StrongVerify;

class StrongVerifyTest extends TestCase
{

    /**
     * Basic Auth0 class config options.
     *
     * @var array
     */
    public static $baseConfig;
    public static $testClient;
    public static $testAddress;
    public static $realClient;
    
    private $faker;

    /**
     * Runs before each test starts
     */
    public function setUp() :void
    {
        parent::setUp();

        $this->faker = \Faker\Factory::create('en_ZA');
        
        // configuration passed to the strongVerify constructor
        // set it up correctly in your env vars for tests to work
        self::$baseConfig = [
            'auth0_domain'        => getenv('AUTH0_DOMAIN') ?? '__AUTH0_DOMAIN__',
            'auth0_client_id'     => getenv('AUTH0_CLIENT_ID') ?? '__AUTH0_CLIENT_ID__',
            'auth0_client_secret' => getenv('AUTH0_CLIENT_SECRET') ?? '__AUTH0_CLIENT_SECRET__',
            'sv_api_base_uri'     => getenv('SV_API_BASE_URI') ?? '__SV_API_BASE_URI__',
        ];

        // a random generated client for testing
        self::$testClient = [
            'email'             => $this->faker->email(),
            'firstName'         => $this->faker->firstName(),       
            'identityNumber'    => $this->faker->idNumber(),
            'lastName'          => $this->faker->lastName(),
            'middleName'        => $this->faker->firstName(),
            'mobileNumber'      => $this->faker->e164PhoneNumber()
        ];

        // a client with details that fits our testing documents
        self::$realClient = [
            'email'             => $this->faker->email(),
            'firstName'         => 'Damian',       
            'identityNumber'    => '9708205014086',
            'lastName'          => 'Walker',
            'middleName'        => 'Christopher',
            'mobileNumber'      => $this->faker->e164PhoneNumber()
        ];

        // a random address to be used for tests
        self::$testAddress = [
            'unit'          => $this->faker->buildingNumber(),
            'building'      => $this->faker->streetName(),
            'streetNumber'  => $this->faker->buildingNumber(),
            'street'        => $this->faker->streetName(),       
            'suburb'        => $this->faker->city(),
            'city'          => $this->faker->city(),
            'code'          => $this->faker->postcode(),
            'province'      => $this->faker->state(),
            'country'       => $this->faker->country(),
            'latitude'      => $this->faker->latitude(),
            'longitude'     => $this->faker->longitude()
        ]; 

    }

    public function testThatEmptyAuth0DomainThrowsExceptions()
    {
        
        $config = self::$baseConfig;
        $key ='auth0_domain';
        $value = $config[$key];
        
        unset($config[$key]);
        putenv(strtoupper($key));
        
        try {
            
            $this->expectException(\Exception::class);
            $strongVerify = new StrongVerify($config);
        
        } catch(\Exception $e){

            putenv(strtoupper($key).'='.$value);
            throw $e;
        
        }
        
        unset($strongVerify);
        unset($config);

    }

    public function testThatEmptyAuth0ClientIdThrowsExceptions()
    {
        
        $config = self::$baseConfig;
        $key ='auth0_client_id';
        $value = $config[$key];
        
        unset($config[$key]);
        putenv(strtoupper($key));
        
        try {
            
            $this->expectException(\Exception::class);
            $strongVerify = new StrongVerify($config);
        
        } catch(\Exception $e){

            putenv(strtoupper($key).'='.$value);
            throw $e;
        
        }
        
        unset($strongVerify);
        unset($config);

    }

    public function testThatEmptyAuth0ClientSecretThrowsExceptions()
    {
        
        $config = self::$baseConfig;
        $key ='auth0_client_secret';
        $value = $config[$key];
        
        unset($config[$key]);
        putenv(strtoupper($key));
        
        try {
            
            $this->expectException(\Exception::class);
            $strongVerify = new StrongVerify($config);
        
        } catch(\Exception $e){

            putenv(strtoupper($key).'='.$value);
            throw $e;
        
        }
        
        unset($strongVerify);
        unset($config);

    }

    public function testThatEmptyAuth0ApiBaseUriThrowsExceptions()
    {
        
        $config = self::$baseConfig;
        $key ='sv_api_base_uri';
        $value = $config[$key];
        
        unset($config[$key]);
        putenv(strtoupper($key));
        
        try {
            
            $this->expectException(\Exception::class);
            $strongVerify = new StrongVerify($config);
        
        } catch(\Exception $e){

            putenv(strtoupper($key).'='.$value);
            throw $e;
        
        }
        
        unset($strongVerify);
        unset($config);

    }

    public function testThatUnauthorisedAccessThrowsAnException()
    {

        $config = self::$baseConfig;
        $config['auth0_client_id'] = '__client_id__';
        $this->expectException(\Exception::class);
        $strongVerify = new StrongVerify($config);
        $token = $strongVerify->getToken();
        $this->assertTrue(is_string($token));
        unset($token);
        unset($strongVerify);
        unset($config);

    }

    public function testThatWeCanGetAnOAuthToken()
    {
        
        $strongVerify = new StrongVerify(self::$baseConfig);
        $token = $strongVerify->getToken();
        $this->assertTrue(is_string($token));
        unset($token);
        unset($strongVerify);

    }

    public function testJwks()
    {

        $strongVerify = new StrongVerify(self::$baseConfig);
        $jwks = $strongVerify->getJwks();
        $this->assertTrue(is_array($jwks));
        unset($jwks);
        unset($strongVerify);

    }

    // public function testClientDelete()
    // {
    //     // $strongVerify = new StrongVerify(self::$baseConfig);
    //     // $strongVerify->getToken();
    //     // $result = $strongVerify->deleteClient('72b884c7-8029-49e6-9f3d-eccb5db029e6');
    //     // //$clientId = $strongVerify->getClient(self::$realClient);
    //     //$this->assertTrue(is_string($clientId));

    // }

    public function testThatWeCanCreateAndRetrieveAClient()
    {

        $strongVerify = new StrongVerify(self::$baseConfig);
        $token = $strongVerify->getToken();
        $id = $strongVerify->createClient(self::$testClient);
        
        $this->assertTrue(is_string($id));

        try {

            $client = $strongVerify->getClient($id);
            
            $this->assertTrue(is_object($client));
            $this->assertTrue($client->statusCode==200);
            $this->assertTrue($client->reasonPhrase=='OK');
            
            foreach (self::$testClient as $key => $value){
            
                $this->assertTrue(isset($client->$key));

            }

        }finally{

            $result = $strongVerify->deleteClient($id);
            $this->assertTrue(is_string($result));

        }

    }

    public function testThatWeCanCRUDAnAddress()
    {

        // set up new client
        $strongVerify = new StrongVerify(self::$baseConfig);
        $strongVerify->getToken();
        $clientId = $strongVerify->createClient(self::$testClient);

        try {

            // create address
            $result = $strongVerify->createAddress($clientId, self::$testAddress);
            $this->assertTrue(is_string($result));
            
            // update address
            $testAddress = self::$testAddress;
            $testAddress['street'] = $this->faker->streetName();
            $result = $strongVerify->updateAddress($clientId, $testAddress);
            $this->assertTrue(is_string($result));

            // retrieve address and check updated street
            $address = $strongVerify->getAddress($clientId);
            $this->assertTrue(is_object($address));
            $this->assertTrue($address->street==$testAddress['street']);

            // delete address
            $result = $strongVerify->deleteAddress($clientId);
            $this->assertTrue(is_string($result));

        }finally{

            $result = $strongVerify->deleteClient($clientId);
            $this->assertTrue(is_string($result));

        }

        unset($strongVerify);

    }

    public function testThatCreatingAnInvalidClientThrowsAnException()
    {

        $strongVerify = new StrongVerify(self::$baseConfig);
        $token = $strongVerify->getToken();

        foreach (self::$testClient as $key => $value){

            $testClient = self::$testClient;
            unset($testClient[$key]);
            
            $result = $strongVerify->createClient($testClient);
            $this->assertTrue($result->statusCode != 200);
            unset($testClient);

        }

        unset($strongVerify);

    }

    public function testThatWeCanUploadAndProcessAnInvalidDocument()
    {

        // set up new client
        $strongVerify = new StrongVerify(self::$baseConfig);
        $strongVerify->getToken();
        $clientId = $strongVerify->createClient(self::$testClient);


        try{

            // upload doc
            // statement|sa_green_barcode_id|account|sa_smart_card_id
            $ref = $strongVerify->uploadDocument($clientId, array(
                'doctype' => 'sa_green_barcode_id',
                'filename' => 'tests/resources/id-example-01.jpg'
            ));
            $this->assertTrue(is_string($ref));

            $state = $strongVerify->getDocumentStatus($ref);
            $this->assertTrue($state=='UPLOADED');

            $state = $strongVerify->processDocument($ref);
            $this->assertTrue($state=='VERIFIED');

            // get info
            $tasks = array('validation', 'preprocessor', 'ocr');

            if (getenv('SV_SAVE_RESULTS')){

                foreach ($tasks as $task){

                    $result = $strongVerify->getDocumentInfo($ref, $task);
                    file_put_contents('tests/results/invalid-'.$ref.'-'.$task.'.json', json_encode($result));

                }

            }

            // download
            $filename = 'tests/results/'.$ref.'-id-example-01.jpg';
            $result = $strongVerify->downloadDocument($ref, $filename);
            $this->assertTrue(file_exists($filename));

            if (!getenv('SV_SAVE_RESULTS')){
                unlink($filename);
            }
        
        }finally{
            
            $result = $strongVerify->deleteClient($clientId);
            $this->assertTrue(is_string($result));
        
        }

    }

    public function testThatWeCanUploadAndProcessAValidDocument()
    {

        // set up new client
        $strongVerify = new StrongVerify(self::$baseConfig);
        $strongVerify->getToken();
        $clientId = $strongVerify->createClient(self::$realClient);
        $this->assertTrue(is_string($clientId));

        try {

            // upload doc
            // statement|sa_green_barcode_id|account|sa_smart_card_id
            $ref = $strongVerify->uploadDocument($clientId, array(
                'doctype' => 'sa_green_barcode_id',
                'filename' => 'tests/resources/id-example-01.jpg'
            ));
            $this->assertTrue(is_string($ref));

            $state = $strongVerify->getDocumentStatus($ref);
            $this->assertTrue($state=='UPLOADED');

            $state = $strongVerify->processDocument($ref);
            $this->assertTrue($state=='VERIFIED');

            // get info
            $tasks = array('validation', 'preprocessor', 'ocr');

            if (getenv('SV_SAVE_RESULTS')){

                foreach ($tasks as $task){

                    $result = $strongVerify->getDocumentInfo($ref, $task);
                    file_put_contents('tests/results/valid-'.$ref.'-'.$task.'.json', json_encode($result));

                }

            }

            // download
            $filename = 'tests/results/'.$ref.'-id-example-01.jpg';
            $result = $strongVerify->downloadDocument($ref, $filename);
            $this->assertTrue(file_exists($filename));
            
            if (!getenv('SV_SAVE_RESULTS')){
                unlink($filename);
            }
        
        }finally{

            $result = $strongVerify->deleteClient($clientId);
            $this->assertTrue(is_string($result));

        }

    }

}