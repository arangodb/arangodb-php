<?php

/**
 * ArangoDB PHP client: connection
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Provides access to the ArangoDB server
 * As all access is done using HTTP, we do not need to establish a
 * persistent connection and keep its state.
 * Instead, connections are established on the fly for each request
 * and are destroyed afterwards.
 *
 * @package ArangoDbPhpClient
 */
class Connection {
  /**
   * Api Version
   * 
   * @var string
   */
  public static  $_apiVersion = '1.0.0';

  /**
   * Connection options
   * 
   * @var array 
   */
  private $_options;
  
  /**
   * Connection handle, used in case of keep-alive
   * 
   * @var resource
   */
  private $_handle;

  /**
   * Flag if keep-alive connections are used
   * 
   * @var bool
   */
  private $_useKeepAlive;

  /**
   * Batches Array
   * 
   * @var array 
   */
  private $_batches = array();
  
  
  /**
   * $_activeBatch boolean
   * 
   * @var array 
   */
   
   
  private $_activeBatch = null;
  
   /**
   * $_captureBatch boolean
   * 
   * @var array 
   */
   
   
  private $_captureBatch = false;
  
   /**
   * $_captureBatch boolean
   * 
   * @var array 
   */
   
   
  private $_batchRequest = false;
  
  /**
   * Set up the connection object, validate the options provided
   *
   * @throws Exception
   * @param array $options - initial connection options
   * @return void
   */
  public function __construct(array $options) {
    $this->_options = new ConnectionOptions($options);
    $this->_useKeepAlive = ($this->_options[ConnectionOptions::OPTION_CONNECTION] === 'Keep-Alive');
  }

  /**
   * Close existing connection handle if a keep-alive connection was used
   *
   * @return void
   */
  public function __destruct() {
    if ($this->_useKeepAlive && is_resource($this->_handle)) {
      @fclose($this->_handle);
    }
  }

  /**
   * Get an option set for the connection
   *
   * @throws ClientException
   * @param string name - name of option
   * @return mixed
   */
  public function getOption($name) {
    assert(is_string($name));

    return $this->_options[$name];
  }
  
  /**
   * Issue an HTTP GET request
   *
   * @throws Exception
   * @param string $url - GET URL
   * @return HttpResponse
   */
  public function get($url) {
    $response = $this->executeRequest(HttpHelper::METHOD_GET, $url, '');
    return $this->parseResponse($response);
  }
  
  /**
   * Issue an HTTP POST request with the data provided
   *
   * @throws Exception
   * @param string $url - POST URL
   * @param string $data - body to post
   * @return HttpResponse
   */
  public function post($url, $data) {
    $response = $this->executeRequest(HttpHelper::METHOD_POST, $url, $data);
    return $this->parseResponse($response);
  }

  /**
   * Issue an HTTP PUT request with the data provided
   *
   * @throws Exception
   * @param string $url - PUT URL
   * @param string $data - body to post
   * @return HttpResponse
   */
  public function put($url, $data) {
    $response = $this->executeRequest(HttpHelper::METHOD_PUT, $url, $data);
    return $this->parseResponse($response);
  }
  
  /**
   * Issue an HTTP PATCH request with the data provided
   *
   * @throws Exception
   * @param string $url - PATCH URL
   * @param string $data - patch body
   * @return HttpResponse
   */
  public function patch($url, $data) {
    $response = $this->executeRequest(HttpHelper::METHOD_PATCH, $url, $data);
    return $this->parseResponse($response);
  }
  
  /**
   * Issue an HTTP DELETE request with the data provided
   *
   * @throws Exception
   * @param string $url - DELETE URL
   * @return HttpResponse
   */
  public function delete($url) {
    $response = $this->executeRequest(HttpHelper::METHOD_DELETE, $url, '');
    return $this->parseResponse($response);
  }


  /**
   * Get a connection handle
   * 
   * If keep-alive connections are used, the handle will be stored and re-used
   *
   * @return resource - connection handle
   */
  private function getHandle() {
    if ($this->_useKeepAlive && $this->_handle && is_resource($this->_handle)) {
      // keep-alive and handle was created already
      $handle = $this->_handle;

      // check if connection is still valid
      if (!feof($handle)) {
        // connection still valid
        return $handle;
      }
      
      // close handle
      @fclose($this->_handle);
      $this->_handle = 0;

      if (!$this->_options[ConnectionOptions::OPTION_RECONNECT]) {
        // if reconnect option not set, this is the end
        throw new ClientException('Server has closed the connection already.');
      }
    }

    // no keep-alive or no handle available yet or a reconnect
    $handle = HttpHelper::createConnection($this->_options);

    if ($this->_useKeepAlive && is_resource($handle)) {
      $this->_handle = $handle; 
    }

    return $handle;
  }

  /**
   * Parse the response return the body values as an assoc array
   *
   * @throws Exception
   * @param HttpResponse $response - the response as supplied by the server
   * @return HttpResponse
   */
  private function parseResponse(HttpResponse $response) {
    $httpCode = $response->getHttpCode();

    if ($httpCode < 200 || $httpCode >= 400) {
      // failure on server
      $details = array();

      $body = $response->getBody();
      if ($body != '') {
        // check if we can find details in the response body
        $details = json_decode($body, true);
        if (is_array($details)) {
         
          // yes, we got details
          $exception = new ServerException($response->getResult(), $httpCode);
          $exception->setDetails($details);
          throw $exception;
        }
      }

      // no details found, throw normal exception
       #var_dump($response);
      throw new ServerException($response->getResult(), $httpCode);
    }

    return $response;
  }
  
  /**
   * Execute an HTTP request and return the results
   * 
   * This function will throw if no connection to the server can be established or if
   * there is a problem during data exchange with the server.
   * 
   * The function might temporarily alter the value of the php.ini value 'default_socket_timeout' but
   * will restore it.
   *
   * @throws Exception
   * @param string $method - HTTP request method
   * @param string $url - HTTP URL
   * @param string $data - data to post in body
   * @return HttpResponse
   */
  private function executeRequest($method, $url, $data) {
    HttpHelper::validateMethod($method);

    // create request data
    if ($this->_batchRequest === false){
      
      if($this->_captureBatch===true){
        $this->_options->offsetSet(ConnectionOptions::OPTION_BATCHPART, true);
        $request = HttpHelper::buildRequest($this->_options, $method, $url, $data);
        $this->_options->offsetSet(ConnectionOptions::OPTION_BATCHPART, false);

      }else{
        $request = HttpHelper::buildRequest($this->_options, $method, $url, $data);
      }
      $batchPart = $this->doBatch($request);
      #var_dump ($batchPart);
      if (!is_null($batchPart) ){
        
        return $batchPart;
      }    
    }else{
      $this->_batchRequest=false;

      $this->_options->offsetSet(ConnectionOptions::OPTION_BATCH, true);
      #var_dump($this->_options);   
                                         
      $request = HttpHelper::buildRequest($this->_options, $method, $url, $data);
      $this->_options->offsetSet(ConnectionOptions::OPTION_BATCH, false);
      #var_dump($this->_options);  
      //var_dump($request);  
    }
    
    
    $traceFunc = $this->_options[ConnectionOptions::OPTION_TRACE];
    if ($traceFunc) {
      // call tracer func
      $traceFunc('send', $request);
    }

    // set socket timeout for this scope 
    $getFunc = function() {
      return ini_get('default_socket_timeout');
    };
    $setFunc = function($value) {
      ini_set('default_socket_timeout', $value);
    };
    #var_dump($request); 
    $scope = new Scope($getFunc, $setFunc);
    $setFunc($this->_options[ConnectionOptions::OPTION_TIMEOUT]);
    // open the socket. note: this might throw if the connection cannot be established
    $handle = $this->getHandle();
    if ($handle) {
      // send data and get response back
      $result = HttpHelper::transfer($handle, $request);
#var_dump($result);
      if (!$this->_useKeepAlive) {
        // must close the connection
        fclose($handle);
      }

      $scope->leave();
      
      if ($traceFunc) {
        // call tracer func
        $traceFunc('receive',$result);
      }

      return new HttpResponse($result);
    } 

    $scope->leave();
    throw new ClientException('Whoops, this should never happen');
  }

    /**
     * Get the client api version
     *
     * @return string
     */
    public static function getVersion() {
        return self::$_apiVersion;
    }
    

    /**
     * Start capturing commands in order to send them over the wire as a batch.
     * @param string $batchId - Identifier of the batch. Can be any valid array-key.
     * @param array $options - Options
     * @return Batch - Returns the active batch object
     */
    public function captureBatch($batchId, $options=array()) {
          

      if (array_key_exists($batchId ,$this->_batches) && is_a($this->_batches[$batchId], 'Batch')){
        $this->_activeBatch=$batchId;
        $this->_captureBatch=true;
        // echo "exists"; var_dump($this->_batches);
      } else {
        $this->_batches[$batchId]=new Batch();
        $this->_activeBatch=$batchId;
        $this->_captureBatch=true;
       // echo "does not exist"; var_dump($this->getActiveBatch());      
      }
      return $this->getActiveBatch();
    }     


    /**
     * Stop capturing commands
     * @param array $options - Options
     * @return Batch - Returns the active batch object
     */
    public function stopCaptureBatch($options=array()) {
      $this->_captureBatch=false;
      return $this->getActiveBatch();
    }     


    /**
    * returns the active batch
    *     
    */
    public function getActiveBatch(){
      return $this->_batches[$this->_activeBatch];
    }


    /**
    * This sends the active batch to the server for batch processing.
    * If a batchId is given, it first sets that batch as active and then sends it.
    * 
    */
    public function processBatch($batchId = '', $options=array()){
    $this->stopCaptureBatch();
    $this->_batchRequest = true;
    $data = '';
    $payload = $this->_batches[$this->_activeBatch]->getPayload();
    if (count($payload)==0) {
      throw new ClientException('Can\'t process empty batch.');
    }
    foreach ($payload as $partKey => $partValue) {
      $data = $data .= '--' . HttpHelper::MIME_BOUNDARY . HttpHelper::EOL;
      
      $data = $data .= 'Content-Type: application/x-arango-batchpart' . HttpHelper::EOL . HttpHelper::EOL;
      $data = $data .= $partValue['request'].HttpHelper::EOL;
    }
    $data= $data .= '--'. HttpHelper::MIME_BOUNDARY . '--' . HttpHelper::EOL . HttpHelper::EOL;

    
    $params = array();
    $url = UrlHelper::appendParamsUrl(Urls::URL_BATCH, $params); 
    $response = $this->post($url, ($data));
    
    $body = $response->getBody();
    
    $body = trim($body, '--'. HttpHelper::MIME_BOUNDARY. '--');
    $batchParts = split('--'. HttpHelper::MIME_BOUNDARY. HttpHelper::EOL , $body);
    
      foreach ($batchParts as $partKey => $partValue) {
        
        $response = new HttpResponse($partValue);
        
              $body = $response->getBody();
              $response = new HttpResponse($body);
              $response = $this->parseResponse($response);

        switch ($payload[$partKey]['type']) {
           case 'document':
              $json=$response->getJson();
              $id=$json[Document::ENTRY_ID];
              $response=$id;
             break;
           case 'edge':
              $json=$response->getJson();
              $id=$json[Edge::ENTRY_ID];
              $response=$id;
             break;
           case 'collection':
              $json=$response->getJson();
              $id=$json[Collection::ENTRY_ID];
              $response=$id;
             break;
           case 'cursor':
              $json=$response->getJson();
              $response=$json;
             break;
           default:
                       
           break;
        }
        
        $responses[]= $response;
      }
      return $responses;
      
    }


    /**
    * This is a helper function to executeRequest that captures requests if we're in batch mode
    * 
    * This checks if we're in batch mode and returns a placeholder object,
    * since we need to return some object that is expected by the caller.
    * if we're not in batch mode it doesn't return anything, and 
    * 
    */
    private function doBatch($request){
      $batchPart=NULL;
      if($this->_captureBatch===true){
        
        $batch=$this->getActiveBatch();
        #var_dump($batch) ;

        $batchPart = $batch->append($request);
      } 
        # do batch processing
      return $batchPart;
      
    }
    
    
}
