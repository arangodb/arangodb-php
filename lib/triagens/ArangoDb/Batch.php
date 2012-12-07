<?php

/**
 * ArangoDB PHP client: batch
 * 
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 * @since 1.1
 * 
 */

namespace triagens\ArangoDb;

/**
 * Provides batching functionality
 *
 * @package ArangoDbPhpClient
 */


class Batch {
  
  private $_batchParts = array();
  
   /**
   * $_captureBatch boolean
   * 
   * @var array 
   */
  

  public function append($request){
    #var_dump($request);
    
    
    
    $result='HTTP/1.1 202 Accepted' . HttpHelper::EOL .'location: /_api/document/0/0' . HttpHelper::EOL .'server: triagens GmbH High-Performance HTTP Server' . HttpHelper::EOL .'content-type: application/json; charset=utf-8' . HttpHelper::EOL .'etag: "0"' . HttpHelper::EOL .'connection: Close' . HttpHelper::EOL . HttpHelper::EOL .'{"error":false,"_id":"0/0","_rev":0}'. HttpHelper::EOL . HttpHelper::EOL;
    
    
    $response=new HttpResponse($result);
    $this->_batchParts[]=array('request' => $request, 'response' => $response);
      return $response;
  }



  public function process(){
    
      return;
  }


    
  public function countParts(){
    
      return;
  }
  
  
  public function setPart($partId) {
  
      return;
  }


  public function getPart($partId) {
  
      return;
  }

  public function getPayload() {
  #var_dump($this->_batchParts);
      return $this->_batchParts;
  }
    
}