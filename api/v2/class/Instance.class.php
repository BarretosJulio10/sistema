<?php

 /**
 * Instance
 */
class Instance extends Conn{

  public $endpoint_create   = "https://api-painel.w-api.app";
  
  public $id_adm = "1716319589869x721327290780988000";

    public function __construct($access_token){
        $this->conn = new Conn;
        $this->pdo  = $this->conn->pdo();

        if(self::verifytoken($access_token)){
            $this->auth = true;
        }else{
            $this->auth = false;
        }

    }

      private function verifytoken($access_token){

        if( isset($access_token) ){
            $access_token   = trim($access_token);

             $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
             $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

             if(count($fetch_consult)>0){

                 $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
                 $fetch_consult = $query_consult->fetch(PDO::FETCH_OBJ);

                 if( $fetch_consult->expire_token > strtotime(date('d-m-Y H:i:s')) ){
                     return true;
                 }else{
                     return false;
                 }

             }else{
                 return false;
             }
        }else{
             return false;
         }

    }


    public function status($headers,$params){

        if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
        }


        if(isset($params['rest'][0])){

          $instance = trim($params['rest'][0]);

          $query_consult = $this->pdo->query("SELECT name as instance, etiqueta, info_api FROM `instances` WHERE name='{$instance}'");
          $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

          if(count($fetch_consult)>0){

              if(!json_decode($fetch_consult[0]->info_api)){
                  return json_encode(array('status' => 'erro', 'message' => 'Tente novamente mais tarde'));
              }
          
                $info_api = json_decode($fetch_consult[0]->info_api);
            
                $curl = curl_init();
                
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://' . $info_api->host . '/instance/info?connectionKey=' . $info_api->connectionKey,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'GET',
                  CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$info_api->token
                  ),
                ));
                
                $response = curl_exec($curl);
                curl_close($curl);


                try{

                    $json = json_decode($response);

                    if(isset($json->error, $json->connection_data->phone_connected)){
                        if(!$json->error){

                             if($json->connection_data->phone_connected){
                                 return json_encode(array('status' => 'success', 'message' =>  'not connected', 'connected' => true));
                             }else{
                                 return json_encode(array('status' => 'error', 'message' =>  'not connected', 'connected' => false));
                             }

                        }else{
                            return json_encode(array('status' => 'error', 'message' =>  'not connected', 'connected' => false));
                        }
                    }else{
                        return json_encode(array('status' => 'error', 'message' =>  'not connected', 'connected' => false));
                    }


                }catch(\Exception  $e){
                    return json_encode(array('status' => 'error', 'message' =>  'not connected', 'connected' => false));
                }

            }else{
                return json_encode(array('status' => 'error', 'message' =>  'instance not found', 'connected' => false));
            }

        }else{
            return json_encode(array('status' => 'error', 'message' =>  'instance not found', 'connected' => false));
        }

     }


   public function create($headers,$params){

       if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
        }

         $name  = $params['name'];
         $token = $params['token'];

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->endpoint_create . '/createNewConnection?id=' . $this->id_adm,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: ••••••'
          ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);


     try{

         $json = json_decode($response);

         if(isset($json->error)){
             if(!$json->error){
                 
                 
                 $this->pdo->query("UPDATE `instances` SET info_api='{$response}' WHERE name='{$token}'");

                 return json_encode(array('status' => 'success', 'message' =>  'instance created'));

             }else{
                 return json_encode(array('status' => 'erro', 'message' =>  'not instance created'));
             }
         }else{
             return json_encode(array('status' => 'erro', 'message' =>  'not instance created'));
         }


     }catch(\Exception  $e){
         return json_encode(array('status' => 'erro', 'message' =>  'erro application'));
     }


  }



  public function disconnect($headers,$params){

        if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
        }


        if(isset($params['rest'][0])){

          $instance = trim($params['rest'][0]);

          $query_consult = $this->pdo->query("SELECT name as instance, etiqueta, info_api FROM `instances` WHERE name='{$instance}'");
          $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

          if(count($fetch_consult)>0){

          
              if(!json_decode($fetch_consult[0]->info_api)){
                  return json_encode(array('status' => 'erro', 'message' => 'Tente novamente mais tarde'));
              }
          
                $info_api = json_decode($fetch_consult[0]->info_api);
            
                $curl = curl_init();
                
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://' . $info_api->host . '/instance/logout?connectionKey=' . $info_api->connectionKey,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'DELETE',
                  CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$info_api->token
                  ),
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);


                try{

                    $json = json_decode($response);

                    if(isset($json->error)){
                        if(!$json->error){

                            return json_encode(array('status' => 'success', 'message' =>  'disconnected', 'disconnected' => true));

                        }else{
                            return json_encode(array('status' => 'error', 'message' =>  'not disconnected', 'disconnected' => false));
                        }
                    }else{
                        return json_encode(array('status' => 'error', 'message' =>  'not disconnected', 'disconnected' => false));
                    }


                }catch(\Exception  $e){
                    return json_encode(array('status' => 'error', 'message' =>  'not disconnected', 'disconnected' => false));
                }

            }else{
                return json_encode(array('status' => 'error', 'message' =>  'instance not found', 'disconnected' => false));
            }

        }else{
            return json_encode(array('status' => 'error', 'message' =>  'instance not found', 'disconnected' => false));
        }

  }

  public function check($headers,$params){


  }

  public function start($headers,$params){
      

      if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
      }

        return true;

  }

  public function qrcode($headers,$params){

          if( !$this->auth ){
                return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
          }


        if(isset($params['rest'][0])){

          $instance = trim($params['rest'][0]);

          $query_consult = $this->pdo->query("SELECT name as instance, etiqueta, info_api FROM `instances` WHERE name='{$instance}'");
          $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

          if(count($fetch_consult)>0){
              
                  if(!json_decode($fetch_consult[0]->info_api)){
                      return json_encode(array('status' => 'erro', 'message' => 'Tente novamente mais tarde'));
                  }
              
                    $info_api = json_decode($fetch_consult[0]->info_api);
                    
                    $curl = curl_init();
                    
                    curl_setopt_array($curl, array(
                      CURLOPT_URL => 'https://' . $info_api->host . '/instance/qrcode?connectionKey=' . $info_api->connectionKey,
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => '',
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 0,
                      CURLOPT_FOLLOWLOCATION => true,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => 'GET',
                      CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '. $info_api->token
                      ),
                    ));
                    
                    $response = curl_exec($curl);

                    curl_close($curl);

                   try{

                       $json = json_decode($response);

                       if(isset($json->error)){


                           if(!$json->error){

                                 return json_encode(array('status' => 'success', 'qrcode' => $json->qrcode));
                    
                         }else{
                            return json_encode(array('status' => 'erro', 'message' => 'Error Application3'));
                         }

                       }else{
                           return json_encode(array('status' => 'erro', 'message' => 'Error Application2'));
                       }


                   }catch(\Exception  $e){
                       return json_encode(array('status' => 'erro', 'message' => 'Error Application1'));
                   }

          }else{
            return json_encode(array('status' => 'erro', 'message' => 'instance not exists'));
          }

        }else{
          return json_encode(array('status' => 'erro', 'message' => 'Method not exists'));
        }

  }



  public function verify($headers,$params){
    if( isset($headers['Access-token']) ){
      $access_token   = trim($headers['Access-token']);

      $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
      $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

      if(count($fetch_consult)>0){

        $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
        $fetch_consult = $query_consult->fetch(PDO::FETCH_OBJ);

          if( $fetch_consult->expire_token > strtotime(date('d-m-Y H:i:s')) ){

            if(isset($params['rest'][0])){

              $instance = trim($params['rest'][0]);

              $query_consult = $this->pdo->query("SELECT name as instance, etiqueta FROM `instances` WHERE name='{$instance}'");
              $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

              if(count($fetch_consult)>0){

                return json_encode(array('status' => 'success', 'data' => $fetch_consult));

              }else{
                return json_encode(array('status' => 'erro', 'message' => 'instance not exists'));
              }

            }else{
              return json_encode(array('status' => 'erro', 'message' => 'Method not exists'));
            }

          }else{
            return json_encode(array('status' => 'erro', 'message' => 'Access Token is expired'));
          }

      }else{
        return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
      }

    }else{
      return json_encode(array('status' => 'erro', 'message' => 'Access Token is required'));
    }

  }

  public function list($headers,$params){
    if( isset($headers['Access-token']) ){
      $access_token   = trim($headers['Access-token']);

      $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
      $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

      if(count($fetch_consult)>0){

        $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
        $fetch_consult = $query_consult->fetch(PDO::FETCH_OBJ);

          if( $fetch_consult->expire_token > strtotime(date('d-m-Y H:i:s')) ){

            $query_consult = $this->pdo->query("SELECT name as instance, etiqueta FROM `instances` WHERE client_id='{$fetch_consult->id}'");
            $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

            return json_encode(array('status' => 'success', 'data' => $fetch_consult));

          }else{
            return json_encode(array('status' => 'erro', 'message' => 'Access Token is expired'));
          }

      }else{
        return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
      }

    }else{
      return json_encode(array('status' => 'erro', 'message' => 'Access Token is required'));
    }

  }

}
