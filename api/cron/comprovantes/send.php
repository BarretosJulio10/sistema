<?php 

  if(isset($_GET['token'])){
       
       if($_GET['token'] == "9IJ89D3-PSLM2JJS29LLKXMSDF02-MKMFDMXBB20"){
           
           
           require_once '../../../panel/class/Conn.class.php';
           require_once '../../../panel/class/Comprovante.class.php';
           require_once '../../../panel/class/Client.class.php';
           
           $key = uniqid().date('his');

           $comprovante = new Comprovante();
           $getComp     = $comprovante->getComprovanteSend();
           $client      = new Client;
           
           
           if($getComp){
               
               
               if($getComp->parceiro != 0){
                   
                   $parceiro_data = $client->getClientByid($getComp->parceiro);
                   
                   if($parceiro_data->whatsapp != NULL){
                       $wpp = $parceiro_data->whatsapp;
                       $info = "\n\nVocê recebeu um comprovante em *cobrei.vc*. Aprove/Recuse em 24hr ou sua conta de parceiro será encerrada.";
                   }else{
                       $wpp = '554599040296';
                       $info = "\n\nComprovante de parceiro. Mas o mesmo não recebeu.";
                   }
                   
                   
               }else{
                   $wpp = '554599040296';
               }
               
               
               $content_message = file_get_contents('message.txt');
       
               $keys_r = array(
                   '{link_comp}' => 'https://cobrei.vc/comp/'.$key,
                   '{info}'      => $info,
                   '{data}'      => date('d/m/Y H:i', strtotime($getComp->data))
                );
               
               $message = str_replace(
                    array_keys($keys_r),
                    array_values($keys_r),
                    $content_message
                );
               
               $params = [
                    "instance"  => 'A07B7C1F07',
                    "text"      => $message,
                    "phone"     => '554599040296'
                ];
                
                $curl = curl_init();
                
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://cobrei.vc/api/v1/message/text',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 1,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS => $params,
                  CURLOPT_HTTPHEADER => array(
                    'Access-token: COBREIVCADMIN',
                    'Cookie: Cookie_2=value'
                  ),
                ));
                
                $response = curl_exec($curl);
                curl_close($curl);
                
                $json = json_decode();
                
            
                $comprovante->setSended($getComp->id);  
                $comprovante->setKey($getComp->id,$key);  
            
               
           }
           
           
       }
       
  }