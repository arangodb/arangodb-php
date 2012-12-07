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
  
 
  /**
  * The array of BatchParts array
  * 
  * @var $_batchParts array 
  */
  private $_batchParts = array();
  
  
  /**
  * put your comment there...
  * 
  * @param mixed $request - 
  * @return HttpResponse
  */
  public function append($request){
    if (preg_match('%/_api/simple/(?P<simple>\w*)|/_api/(?P<direct>\w*)%ix', $request, $regs)) {
      $result = $regs[0];
    } else {
      $result = "";
    }

    $type = $regs['direct']!='' ? $regs['direct'] : $regs['simple'] ;
    
    $result  = 'HTTP/1.1 202 Accepted' . HttpHelper::EOL;
    $result .= 'location: /_api/document/0/0' . HttpHelper::EOL ;
    $result .= 'server: triagens GmbH High-Performance HTTP Server' . HttpHelper::EOL;
    $result .= 'content-type: application/json; charset=utf-8' . HttpHelper::EOL;
    $result .= 'etag: "0"' . HttpHelper::EOL;
    $result .= 'connection: Close' . HttpHelper::EOL . HttpHelper::EOL;
    $result .= '{"error":false,"_id":"0/0","_rev":0}'. HttpHelper::EOL . HttpHelper::EOL;
    
    $response=new HttpResponse($result);
    $this->_batchParts[]=array('type' => $type, 'request' => $request, 'response' => $response);
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