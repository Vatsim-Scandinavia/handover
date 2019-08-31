<?php

require('OAuth.php');
require('SSO.class.php');

class Authentication{

  private $SSOHandler;
  private $loggedIn;
  private $User;
  private $returnURL;
  
  function __construct( $base, $key, $secret, $method, $cert ){
    
    $this->Base = $base;
    $this->Key = $key;
    $this->Secret = $secret;
    $this->Method = $method;
    $this->Cert = $cert;
    
    $this->returnURL = $this->returnURL . "?return";
    $this->loggedIn = false;
    $this->User = array();
    
    if ( session_status() === PHP_SESSION_NONE ){
      session_start();
    }
    
    if( isset( $_SESSION['AuthHandler'] ) ){
    
      $StoredHandler = unserialize( $_SESSION['AuthHandler'] );
      $this->User = $StoredHandler->getUserDetails();
      $this->loggedIn = $StoredHandler->isLoggedIn();
      
    }
    
    $this->SSO = new SSO( $this->Base, $this->Key, $this->Secret, $this->Method, $this->Cert );
    
  }

  public function Login(){
    
    $token = $this->SSO->requestToken( $this->returnURL, false, false );
    if ( $token ){
      
        $_SESSION['oauth'] = array(
            'key' => (string)$token->token->oauth_token,
            'secret' => (string)$token->token->oauth_token_secret
        );
        
        return $this->SSO->sendToVatsim();
        
    } else {
      
        return false;
        
    }
    
  }
  
  public function checkLogin(){
    
    if ( isset( $_GET['return'] ) && isset( $_GET['oauth_verifier'] ) && !isset( $_GET['oauth_cancel'] ) ){
      
      if ( isset( $_SESSION['oauth'] ) && isset( $_SESSION['oauth']['key'] ) && isset( $_SESSION['oauth']['secret'] ) ){
        
        if ( @$_GET['oauth_token'] != $_SESSION['oauth']['key'] ){
          
          return false;
          
        }
        if (@!isset($_GET['oauth_verifier'])){
          
          return false;
          
        }
        
        $user = $this->SSO->checkLogin( $_SESSION['oauth']['key'], $_SESSION['oauth']['secret'],  @$_GET['oauth_verifier'] );
        
        if( $user ){
          
          unset( $_SESSION['oauth'] );
          $this->User = $user->user;
          $this->loggedIn = true;
          return true;
          
        } else {
          
          return false;
          
        }
      } 
    } else if ( isset( $_GET['return'] ) && isset( $_GET['oauth_cancel'] ) ){
      
        return false;
        
    }      
    
  }
  
  public function shouldCheckLogin(){
    
    return isset( $_GET['return'] ) && isset( $_GET['oauth_verifier'] ) && !isset( $_GET['oauth_cancel'] );
    
  }
  
  public function getUserDetails(){
    
    if( $this->loggedIn ){
      
      return $this->User;
      
    }
    
    return false;
    
  }
    
  public function isLoggedIn(){
    
    return $this->loggedIn;
    
  }
  
  public function Logout(){
    
    $this->User = Array();
    $this->loggedIn = false;
    unset( $_SESSION["AuthHandler"] );
    
  }
  
}
