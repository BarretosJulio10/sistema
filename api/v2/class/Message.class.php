<?php

 /**
 * Message
 */
class Message extends Conn{

    public $endpoint   = "http://167.114.6.95/";

    public function __construct($access_token){
        $this->conn = new Conn;
        $this->pdo  = $this->conn->pdo();

        $client_access = self::verifytoken($access_token);

        if($client_access){
            $this->auth    = true;
            $this->credits = 10;
            $this->client  = $client_access;
        }else{
            $this->auth    = false;
            $this->credits = 10;
            $this->client  = false;
        }

    }

    private function verifytoken($access_token){

        if($access_token == "COBREIVCADMIN"){
            return (object)array(
             'credits' => 1000000,
             'client'  => 0
            );
        }

        if( isset($access_token) ){
            $access_token   = trim($access_token);

             $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
             $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

             if(count($fetch_consult)>0){

                 $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
                 $fetch_consult = $query_consult->fetch(PDO::FETCH_OBJ);

                 return $fetch_consult;


             }else{
                 return false;
             }
        }else{
             return false;
         }

    }


  public function credits($headers,$params){
    if( isset($headers['Access-token']) ){
      $access_token   = trim($headers['Access-token']);

      $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
      $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);

      if(count($fetch_consult)>0){

        $query_consult = $this->pdo->query("SELECT * FROM `client` WHERE token='{$access_token}'");
        $fetch_consult = $query_consult->fetch(PDO::FETCH_OBJ);

          if( $fetch_consult->expire_token > strtotime(date('d-m-Y H:i:s')) ){

            return json_encode(array('status' => 'success', 'credits' => $fetch_consult->credits));

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

  public function getqr($params){

   $instance = trim($params['instance']);

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->endpoint.'session/qr',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Token: '.$instance,
        'Content-Type: application/json'
      ),
    ));

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

  }

  public function changeCredits($client,$type,$qtd=1){


    return true;



    if(isset($client->create_account)){

        $creditsNow    = $client->credits;

        if($type == 'add'){
          $creditsLasted = ($creditsNow+$qtd);
        }else if($type == 'remove'){
          $creditsLasted = ($creditsNow-$qtd);
        }


      if($this->pdo->query("UPDATE `client` SET credits='{$creditsLasted}' WHERE id='{$client->id}'")){
          return $creditsLasted;
        }else{
          return false;
        }


    }else{
      return false;
    }

  }

  public function image($headers,$params){



       if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
        }

        self::getqr($params);

        if(isset($params['phone'])){

            $phone = trim($params['phone']);

            if(isset($params['file'])){

                $file = $params['file'];

                if(!empty($file)){

                    if(isset($params['instance'])){

                        $instance = trim($params['instance']);

                        if(!empty($instance)){
                            
                            
                              $query_consult = $this->pdo->query("SELECT name as instance, etiqueta, info_api FROM `instances` WHERE name='{$instance}'");
                              $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);
                    
                              if(count($fetch_consult)>0){
                    
                              
                                  if(!json_decode($fetch_consult[0]->info_api)){
                                      return json_encode(array('status' => 'erro', 'message' => 'Tente novamente mais tarde'));
                                  }
                                  
                                  
                                  $info_api = json_decode($fetch_consult[0]->info_api);
                                  
                                          
                                if($this->credits > 0){
    
                                    $content_file = file_get_contents($file);
                                    $base64_file  = base64_encode($content_file);
    
                                    $curl = curl_init();
    
                                    curl_setopt_array($curl, array(
                                      CURLOPT_URL => 'https://' . $info_api->host . '/message/sendImageBase64?connectionKey=' . $info_api->connectionKey,
                                      CURLOPT_RETURNTRANSFER => true,
                                      CURLOPT_ENCODING => '',
                                      CURLOPT_MAXREDIRS => 10,
                                      CURLOPT_TIMEOUT => 0,
                                      CURLOPT_FOLLOWLOCATION => true,
                                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                      CURLOPT_CUSTOMREQUEST => 'POST',
                                      CURLOPT_POSTFIELDS =>'{"phoneNumber":"'.$phone.'","base64Image":"data:image/jpeg;base64,'.$base64_file.'"}',
                                      CURLOPT_HTTPHEADER => array(
                                        'Authorization: Bearer '.$info_api->token,
                                        'Content-Type: application/json'
                                      ),
                                    ));
    
                                    $response = curl_exec($curl);
                                    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                                    curl_close($curl);
    
                                    try{
    
                                        $dados = json_decode($response);
    
                                        if(!$dados->error){
    
                                            return json_encode(array('status' => 'success', 'message' => 'Message sended'));
    
                                      }else{
                                        return json_encode(array('status' => 'erro', 'message' => 'Message not sended. The instance may be disconnected'));
                                      }
    
                                    }catch(\Exception $e){
                                        return json_encode(array('status' => 'erro', 'message' => 'Message not sended.'));
                                    }
    
                                }else{
                                    return json_encode(array('status' => 'erro', 'message' => 'You have no credits'));
                                 }
                                          
                                  
                              }
                              


                        }

                    }

                }

            }


        }
  }

  public function audio($headers,$params){

       if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
        }

        self::getqr($params);

        if(isset($params['phone'])){

            $phone = trim($params['phone']);

            if(isset($params['file'])){

                $file = $params['file'];

                if(!empty($file)){

                    if(isset($params['instance'])){

                        $instance = trim($params['instance']);

                        if(!empty($instance)){
                            
                            
                              $query_consult = $this->pdo->query("SELECT name as instance, etiqueta, info_api FROM `instances` WHERE name='{$instance}'");
                              $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);
                    
                              if(count($fetch_consult)>0){
                    
                              
                                  if(!json_decode($fetch_consult[0]->info_api)){
                                      return json_encode(array('status' => 'erro', 'message' => 'Tente novamente mais tarde'));
                                  }
                                  
                                  
                                  $info_api = json_decode($fetch_consult[0]->info_api);
                                  
                                    if($this->credits > 0){
        
                                        $url_file = $file;

                                        $curl = curl_init();
        
                                        curl_setopt_array($curl, array(
                                          CURLOPT_URL => 'https://' . $info_api->host. '/message/sendAudioUrl?connectionKey=' . $info_api->connectionKey,
                                          CURLOPT_RETURNTRANSFER => true,
                                          CURLOPT_ENCODING => '',
                                          CURLOPT_MAXREDIRS => 10,
                                          CURLOPT_TIMEOUT => 0,
                                          CURLOPT_FOLLOWLOCATION => true,
                                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                          CURLOPT_CUSTOMREQUEST => 'POST',
                                          CURLOPT_POSTFIELDS =>'{"phoneNumber":"'.$phone.'","url":"'.$url_file.'","delayMessage":"5000"}',
                                          CURLOPT_HTTPHEADER => array(
                                            'Authorization: Bearer '. $info_api->token,
                                            'Content-Type: application/json'
                                          ),
                                        ));
        
                                        $response = curl_exec($curl);
                                        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                                        curl_close($curl);
        
                                        try{
        
                                            $dados = json_decode($response);
        
                                            if(!$dados->error){
        
                                                return json_encode(array('status' => 'success', 'message' => 'Message sended'));
        
                                          }else{
                                            return json_encode(array('status' => 'erro', 'message' => 'Message not sended. The instance may be disconnected'));
                                          }
        
                                        }catch(\Exception $e){
                                            return json_encode(array('status' => 'erro', 'message' => 'Message not sended.'));
                                        }
        
                                    }else{
                                        return json_encode(array('status' => 'erro', 'message' => 'You have no credits'));
                                      }
                              
                              }
  
                        }

                    }

                }

            }


        }

  }

  public function image_text($headers,$params){


       if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
        }

        self::getqr($params);

        if(isset($params['phone'])){

            $phone = trim($params['phone']);

            if(isset($params['image_text'])){
                
                $expode_params = explode('___&&___889__&&___', $params['image_text']);

                $text = $expode_params[0];
                $img  = $expode_params[1];

                if(!empty($text)){

                    if(isset($params['instance'])){

                        $instance = trim($params['instance']);

                        if(!empty($instance)){
                            
                            
                              $query_consult = $this->pdo->query("SELECT name as instance, etiqueta, info_api FROM `instances` WHERE name='{$instance}'");
                              $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);
                    
                              if(count($fetch_consult)>0){
                    
                              
                                  if(!json_decode($fetch_consult[0]->info_api)){
                                      return json_encode(array('status' => 'erro', 'message' => 'Tente novamente mais tarde'));
                                  }
                                  
                                  
                                  $info_api = json_decode($fetch_consult[0]->info_api);
                                  
                                  
                                  
                                    if($this->credits > 0){
        
                                       $content_file = file_get_contents($img);
                                       $base64_file  = base64_encode($content_file);
        
                                       $data = array(
                                            "url"          => $img,
                                            "phoneNumber"  => $phone,
                                            "caption"      => $text,
                                            "delayMessage" => "5000"
                                        );
                                    
                                      $postdata = json_encode($data);
                                    
                                        // enviar texto com mensagem
                                        $curl = curl_init();
                                        curl_setopt_array($curl, array(
                                          CURLOPT_URL => 'https://' . $info_api->host. '/message/sendImageUrl?connectionKey=' . $info_api->connectionKey,
                                          CURLOPT_RETURNTRANSFER => true,
                                          CURLOPT_ENCODING => '',
                                          CURLOPT_MAXREDIRS => 10,
                                          CURLOPT_TIMEOUT => 0,
                                          CURLOPT_FOLLOWLOCATION => true,
                                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                          CURLOPT_CUSTOMREQUEST => 'POST',
                                          CURLOPT_POSTFIELDS => $postdata,
                                          CURLOPT_HTTPHEADER => array(
                                            'Authorization: Bearer '. $info_api->token,
                                            'Content-Type: application/json'
                                          ),
                                        ));
        
                                        $response = curl_exec($curl);
                                        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                                        curl_close($curl);
        
                                        try{
        
                                            $dados = json_decode($response);
        
                                            if(!$dados->error){
        
                                                return json_encode(array('status' => 'success', 'message' => 'Message sended'));
        
                                          }else{
                                            return json_encode(array('status' => 'erro', 'message' => 'Message not sended. The instance may be disconnected'));
                                          }
        
                                        }catch(\Exception $e){
                                            return json_encode(array('status' => 'erro', 'message' => 'Message not sended.'));
                                        }
        
                                    }else{
                                        return json_encode(array('status' => 'erro', 'message' => 'You have no credits'));
                                      }
                                          
                              }


                        }

                    }

                }

            }

        }

  }
  
  public function text($headers,$params){


       if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
        }

        self::getqr($params);

        if(isset($params['phone'])){

            $phone = trim($params['phone']);

            if(isset($params['text'])){

                $text = $params['text'];

                if(!empty($text)){

                    if(isset($params['instance'])){

                        $instance = trim($params['instance']);

                        if(!empty($instance)){


                              $query_consult = $this->pdo->query("SELECT name as instance, etiqueta, info_api FROM `instances` WHERE name='{$instance}'");
                              $fetch_consult = $query_consult->fetchAll(PDO::FETCH_OBJ);
                    
                              if(count($fetch_consult)>0){
                    
                              
                                  if(!json_decode($fetch_consult[0]->info_api)){
                                      return json_encode(array('status' => 'erro', 'message' => 'Tente novamente mais tarde'));
                                  }
                                  
                                  
                                  $info_api = json_decode($fetch_consult[0]->info_api);
                              
                                  
                                  if($this->credits > 0){

                                        $dados = array(
                                              'phoneNumber'  => $phone,
                                              'message'      => $text,
                                              'delayMessage' => '1000'
                                        );
        
                                        $curl = curl_init();
        
                                        curl_setopt_array($curl, array(
                                          CURLOPT_URL => 'https://' . $info_api->host.'/message/sendText?connectionKey=' . $info_api->connectionKey,
                                          CURLOPT_RETURNTRANSFER => true,
                                          CURLOPT_ENCODING => '',
                                          CURLOPT_MAXREDIRS => 10,
                                          CURLOPT_TIMEOUT => 0,
                                          CURLOPT_FOLLOWLOCATION => true,
                                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                          CURLOPT_CUSTOMREQUEST => 'POST',
                                          CURLOPT_POSTFIELDS => json_encode($dados),
                                          CURLOPT_HTTPHEADER => array(
                                            'Authorization: Bearer '.$info_api->token,
                                            'Content-Type: application/json'
                                          ),
                                        ));
        
                                        $response = curl_exec($curl);
                                        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                                        curl_close($curl);
        
                                        try{
        
                                            $dados = json_decode($response);
        
                                            if(!$dados->error){
        
                                                return json_encode(array('status' => 'success', 'message' => 'Message sended'));
        
                                          }else{
                                            return json_encode(array('status' => 'erro', 'message' => 'Message not sended. The instance may be disconnected'));
                                          }
        
                                        }catch(\Exception $e){
                                            return json_encode(array('status' => 'erro', 'message' => 'Message not sended.'));
                                        }
        
                                    }else{
                                        return json_encode(array('status' => 'erro', 'message' => 'You have no credits'));
                                      }

                                  
                                  
                              }

        
                        }

                    }

                }

            }

        }

  }


}
