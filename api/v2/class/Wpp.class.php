<?php


 /**
  *
  */
 class Wpp extends Conn
 {


      public $auth = false;

      public $endpoint = 'http://167.114.6.95/';


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


    public function createInstance($headers,$params){

       if( !$this->auth ){
            return json_encode(array('status' => 'erro', 'message' => 'Access Token invalid'));
        }

         $name  = $params['name'];
         $token = $params['token'];

         $curl = curl_init();

         curl_setopt_array($curl, array(
           CURLOPT_URL => $this->endpoint.'/devices/create?name='.$name.'&token='.trim($token),
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => '',
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 2,
           CURLOPT_FOLLOWLOCATION => true,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => 'POST',
           CURLOPT_HTTPHEADER => array(
             'Token: 1234ABCD',
             'Content-Type: application/json'
           ),
         ));

         $response = curl_exec($curl);
         curl_close($curl);

         return $response;


     try{

         $json = json_decode($response);

         if(isset($json->success)){
             if($json->success == true){

                 if(isset($json->data->name)){
                     if($json->data->name != ""){
                         return json_encode(array('status' => 'success', 'message' =>  'instance created'));
                     }else{
                        return json_encode(array('status' => 'erro', 'message' =>  'not instance created'));
                     }
                 }else{
                     return json_encode(array('status' => 'erro', 'message' =>  'not instance created'));
                 }

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

  public function sendMessage($headers,$params){

       if( !$this->auth ){
            return false;
        }

		$data = array(
					"Id"     => strval(rand(99999,9999999)),
					"Phone"  => $params['phone'],
					"Body"   => $params['message'],
		 );

		$instance = trim($params['instance']);


	    $postdata = json_encode($data);

		$curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->endpoint.'/chat/send/text',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 1,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => $postdata,
          CURLOPT_HTTPHEADER => array(
            'token: '.$instance,
            'Content-Type: application/json'
          ),
        ));

		$response = curl_exec($curl);
		curl_close($curl);

        var_dump($response);

  }

  public function getStatus($headers,$params){

      if( !$this->auth ){
            return false;
       }

     $instance = trim($params['instance']);

     $curl = curl_init();

     curl_setopt_array($curl, array(
       CURLOPT_URL => $this->endpoint.'/session/status',
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => 'GET',
       CURLOPT_HTTPHEADER => array(
         'Token: '.trim($instance)
       ),
     ));

     $response = curl_exec($curl);

     curl_close($curl);


     try{

         $json = json_decode($response);

         if(isset($json->success)){
             if($json->success == true){

                  if($json->data->Connected == false && $json->data->LoggedIn == false){
                      return false;
                  }else if($json->data->Connected == true && $json->data->LoggedIn == false){
                      return false;
                  }else if($json->data->Connected == false && $json->data->LoggedIn == true){
                      return false;
                  }else if($json->data->Connected == true && $json->data->LoggedIn == true){
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


     }catch(\Exception  $e){
         return false;
     }


  }

    public function verifyWhatsapp($headers,$params){


      if( !$this->auth ){
            return false;
       }

       $instance = trim($params['instance']);
       $phone    = trim($params['phone']);


       $curl = curl_init();

       curl_setopt_array($curl, array(
         CURLOPT_URL => $this->endpoint.'/user/check',
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'POST',
         CURLOPT_POSTFIELDS =>'{"Phone":["'.$phone.'"]}',
         CURLOPT_HTTPHEADER => array(
           'token: '.trim($instance),
           'Content-Type: application/json'
         ),
       ));

       $response = curl_exec($curl);
       curl_close($curl);

       try{

           $json = json_decode($response);

           if(isset($json->code)){
               if($json->code == 200){
                   return $response;
               }else{
                   return false;
               }
           }else{
               return false;
           }


       }catch(\Exception  $e){
           return false;
       }

    }


    public function logOut($headers,$params){

       if( !$this->auth ){
            return false;
       }

       $instance = trim($params['instance']);

       $curl = curl_init();

       curl_setopt_array($curl, array(
         CURLOPT_URL => $this->endpoint.'/session/logout',
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'POST',
         CURLOPT_HTTPHEADER => array(
           'Token: '.trim($instance)
         ),
       ));

       $response = curl_exec($curl);
       curl_close($curl);

       try{

           $json = json_decode($response);

           if(isset($json->success)){
               if($json->success == true){
                   return true;
               }else{
                   return false;
               }
           }else{
               return false;
           }


       }catch(\Exception  $e){
           return false;
       }

    }

    public function getqrcode($headers,$params){

       if( !$this->auth ){
            return false;
       }

       $instance = trim($params['instance']);

       $curl = curl_init();

       curl_setopt_array($curl, array(
         CURLOPT_URL => $this->endpoint.'/session/qr',
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array(
           'Token: '.trim($instance)
         ),
       ));

       $response = curl_exec($curl);
       

       curl_close($curl);

       try{

           $json = json_decode($response);

           if(isset($json->success)){


               if($json->success == false){

                   if(isset($json->error)){
                       if($json->error == "Already Loggedin"){
                           echo json_encode(['erro' => false, 'connected' => true]);
                           exit;
                       }else{
                           return false;
                       }
                   }else{
                       return false;
                   }

               }else if($json->success == true){

                   if(isset($json->data->QRCode)){
                       return $json->data->QRCode;
                   }else{
                       return false;
                   }

             }else{
                   return false;
              }
           }else{
               return false;
           }


       }catch(\Exception  $e){
           return false;
       }


    }

    public function startWhatsapp($headers,$params){

        if( !$this->auth ){
            return false;
        }

      $instance = trim($params['instance']);


      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => $this->endpoint.'/session/connect',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{"Subscribe":["Message"],"Immediate":false}',
        CURLOPT_HTTPHEADER => array(
          'Token: '.trim($instance),
          'Content-Type: application/json'
        ),
      ));

      $response = curl_exec($curl);
      curl_close($curl);

      try{

              $json = json_decode($response);

              if(isset($json->success)){

                  return true;

              }else{
                  return false;
              }


          }catch(\Exception  $e){
              return false;
          }



    }



 }




?>
