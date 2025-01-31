<?php 

   if(isset($_GET['token'])){
       
       if($_GET['token'] == "kSXx6pp1FuHqEZUG5ETFCSoiu4hi0nwnZ5s51lBQ2g9S"){
           
           require_once '../../panel/config.php';
           require_once '../../panel/class/Conn.class.php';
           require_once '../../panel/class/Messages.class.php';
           require_once '../../panel/class/Plans.class.php';
           require_once "../../panel/class/Invoice.class.php";
           
           $messages = new Messages();
           $plans    = new Plans();
           $invoice  = new Invoice();
           
           $message_fila = $messages->getFila();
           
           if($message_fila){
               
               if(json_decode($message_fila->content)){
                   
                   $assinante = $messages->getSignature($message_fila->assinante_id);
                   
                   if(!$assinante){
                       exit;
                   }
                   
                   $client = $messages->getClient($message_fila->client_id);
                   
                   if(!$client){
                       exit;
                   }
                   
                   
                   $invoiceData = $invoice->getInvoiceOpen($assinante->id);
                   
                   $dados_message = json_decode($message_fila->content);
                   
                    if($invoiceData){
                        
                       $plan_ass = $plans->getPlanByid($invoiceData->plan_id);
                        
                    }
                   
                   $error = array();
                   
                   foreach($dados_message as $key => $value){
                       
                       $message = "";
                       $name_p  = "text";
                       
                       if($value->type == "audio"){
                           $message = SITE_URL."/panel/cdn/audios/audio_{$message_fila->template_id}_{$key}.ogg?v=".uniqid();
                           $name_p  = "file";
                       }else if($value->type == "image"){
                           $message = SITE_URL."/panel/cdn/images/image_{$message_fila->template_id}_{$key}.jpeg?v=".uniqid();
                           $name_p  = "file";
                       }else if($value->type == "image_text"){
                           
                           $img_caption = SITE_URL."/panel/cdn/images/image_{$message_fila->template_id}_{$key}.jpeg?v=".uniqid();
                           $name_p      = "image_text";
                           
                           $message = $value->content;
                           
                           $array_replace = array(
                                '{client_name}'   => $assinante->nome,
                                '{client_whats}'  => $assinante->ddi.$assinante->whatsapp,
                                '{plan_value}'    => $plan_ass ? $plan_ass->valor : '',
                                '{link_fatura}'   => $invoiceData ? SITE_URL.'/'.base64_decode($invoiceData->ref) : '',
                                '{plan_name}'     => $plan_ass ? $plan_ass->nome : '',
                                '{date}'          => date('d/m/Y'),
                                '{client_expire}' => date('d/m/Y', strtotime($assinante->expire_date))
                               );
                               
                              
                          $message = str_replace(array_keys($array_replace), array_values($array_replace), $message) . '___&&___889__&&___'.$img_caption;
                          
                       }else{
                           $message = $value->content;
                           
                           $array_replace = array(
                                '{client_name}'   => $assinante->nome,
                                '{client_whats}'  => $assinante->ddi.$assinante->whatsapp,
                                '{plan_value}'    => $plan_ass ? $plan_ass->valor : '',
                                '{link_fatura}'   => $invoiceData ? SITE_URL.'/'.base64_decode($invoiceData->ref) : '',
                                '{plan_name}'     => $plan_ass ? $plan_ass->nome : '',
                                '{date}'          => date('d/m/Y'),
                                '{client_expire}' => date('d/m/Y', strtotime($assinante->expire_date))
                               );
                               
                              
                          $message = str_replace(array_keys($array_replace), array_values($array_replace), $message);
                    
                       }
                       

                    
                      if($value->type != "text"){
                          
                          
                          if($value->type == "audio"){
                              if(!is_file("../../panel/cdn/audios/audio_{$message_fila->template_id}_{$key}.ogg")){
                                  $error[$key] = true;
                                  continue;
                              }
                          }else if($value->type == "image"){
                              if(!is_file("../../panel/cdn/images/image_{$message_fila->template_id}_{$key}.jpeg")){
                                  $error[$key] = true;
                                  continue;
                              }
                          }else if($value->type == "image_text"){
                              if(!is_file("../../panel/cdn/images/image_{$message_fila->template_id}_{$key}.jpeg")){
                                  $error[$key] = true;
                                  continue;
                              }
                          }
                          
                          
                        }
                        
                        
                        if($value->type != "text" && $value->type != "audio" && $value->type != "image" && $value->type != "image_text"){
                            $value->type = "text";
                        }
                        
                        $params = [
                            "instance"  => $message_fila->instance_id,
                            "{$name_p}" => $message,
                            "phone"     => $message_fila->phone
                        ];
                        
                        $curl = curl_init();
                        
                        curl_setopt_array($curl, array(
                          CURLOPT_URL => SITE_URL.'/api/'.VERSION_API_WPP.'/message/'.$value->type,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => '',
                          CURLOPT_MAXREDIRS => 10,
                          CURLOPT_TIMEOUT => 1,
                          CURLOPT_FOLLOWLOCATION => true,
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => 'POST',
                          CURLOPT_POSTFIELDS => $params,
                          CURLOPT_HTTPHEADER => array(
                            'Access-token: '.$client->token,
                            'Cookie: Cookie_2=value'
                          ),
                        ));
                        
                        $response = curl_exec($curl);
                        curl_close($curl);
                        
                       
                   }
                   
                   
                   $messages->removeFila($message_fila->id);
                   http_response_code(200);
                   

               }
               
               
           }
            
       }
       
       
   }