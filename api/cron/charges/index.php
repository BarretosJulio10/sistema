<?php

  /*cobrei.vc*/
  // @AUTHOR: Luan Alves
  // @DATE: 16/05/2023
  // api-whats.com

 // header("Content-type: application/json; charset=utf-8");
  date_default_timezone_set('America/Sao_Paulo');

  if(isset($_REQUEST['url'])){

    $url       = explode('/',$_REQUEST['url']);
    $client_id = trim($url[0]);
    
    $uniq    = isset($_REQUEST['uniq']) ? $_REQUEST['uniq'] : false;
    $plan_id = isset($_REQUEST['plan_id']) ? $_REQUEST['plan_id'] : false;

    require_once "../../../panel/config.php";
    require_once "../../../panel/class/Conn.class.php";
    require_once "../../../panel/class/Charges.class.php";
    require_once "../../../panel/class/Options.class.php";
    require_once "../../../panel/class/Invoice.class.php";
    require_once "../../../panel/class/Cron.class.php";

    $charges = new Charges($client_id);
    $options = new Options($client_id);
    $invoice = new Invoice($client_id);
    $cron    = new Invoice($client_id);

    // get client 
    $client = $charges->getClient();
    
    if($client){
        
        // verifica a data de expiração
        if(strtotime('now') > $client->due_date){
            echo 'expired';
            exit;
        }
        
        // verifica setting charge
        $setting_charge = $options->getOption('setting_charge', true);

        if($setting_charge){
            
            $setting_charge = json_decode($setting_charge);
            
            // verificar se existe cobrancas apos o vencimento
            $setting_charge_last = json_decode($options->getOption('setting_charge_last', true));
            $setting_charge_interval = json_decode($options->getOption('setting_charge_interval', true));
            
            $last_charge  = false;
            $interval_charge = false;
            $dates_lasted = array();


            if(isset($setting_charge_last->active)){
               if($setting_charge_last->active == 1){
                $dates_lasted[1] = $setting_charge_last->charge_last_1;
                $dates_lasted[2] = $setting_charge_last->charge_last_2;
                $dates_lasted[3] = $setting_charge_last->charge_last_3;
                $dates_lasted[4] = $setting_charge_last->charge_last_4;
                $last_charge = true;
               }
            }
            
            if(isset($setting_charge_interval->active)){
                if($setting_charge_interval->active == 1){
                    $last_charge = false;
                    $interval_charge = true;
                }
            }
            
            $date_now  = date('Y-m-d');
            if($setting_charge->days_antes_charge != '0'){
                $totime    = strtotime('+'.$setting_charge->days_antes_charge.' days', strtotime(date('Y-m-d')));
                $next_data = date('Y-m-d', $totime);
            }else{
                $totime    = strtotime('now');
                $next_data = date('Y-m-d', $totime);
            }

            if($interval_charge) {
                if($setting_charge_interval->next_date == date('d-m-Y')){
                    file_get_contents( SITE_URL . '/api/cron/charges/interval/'.$client_id);
                    echo 'teste';
                }
            }

            // get signatures
            $signatures = $charges->getSignaturesExpire($date_now, $next_data, $uniq, $last_charge, $dates_lasted);

            if($signatures){

                if($setting_charge->days_charge != "false"){

                    // verifica whatsapp
                    $instance = $charges->getInstanceByClient();

                    if($instance){

                        foreach($signatures as $key => $signature){



                            if ($plan_id) {
                                $plan = $charges->getPlanbyId($plan_id);
                            }
                            else {
                                var_dump($signature->plan_id);
                                $plan = $charges->getPlanbyId($signature->plan_id);
                            }
            
                            if($plan){
                                
                                $invoiceLasted = $invoice->getInvoiceOpen($signature->id);
                                
                                // expirate invoice
                                $expirate_days_invocie = !isset($setting_charge->expire_date_days) ? 7 : (int)$setting_charge->expire_date_days;
                                $expirate_invoice = strtotime('+'.$expirate_days_invocie.' days', strtotime('now'));
                                
                                // criar fatura
                                $dadosInvoice               = new stdClass();
                                $dadosInvoice->id_assinante = $signature->id;
                                $dadosInvoice->assinante_id = $dadosInvoice->id_assinante;
                                $dadosInvoice->status       = 'pending';
                                $dadosInvoice->value        = $plan->valor;
                                $dadosInvoice->plan_id      = $plan->id;
                                $dadosInvoice->expire_date  = date('Y-m-d H:i:s', $expirate_invoice);
                                $dadosInvoice->client_id    = $client->id;
                                if($invoiceLasted == false){
                                    $invoiceAdd             = $invoice->addInvoice($dadosInvoice,true);
                                }else{
                                    $invoiceAdd             = $invoiceLasted->id;
                                }
                                
                                $invoiceData                = $invoice->getInvoiceByid($invoiceAdd);
                                $dadosInvoice->invoice_id   = $invoiceData->id;

                                if($signature->expired < 1){
                                    $template_message = $charges->getTemplateById($plan->template_charge);
                                }else{
                                    $template_message = $charges->getTemplateById($plan->template_late);
                                    $template_message = $template_message ? $template_message : $charges->getTemplateById($plan->template_charge);
                                }

                                                  
                                if($template_message){
                                    
                                     $dados_template = json_decode($template_message->texto);
                                    
                                      foreach($dados_template as $keyTempalte => $tema){
                                          if($tema->type == "pix"){
                                              require_once 'pay/pix.php';
                                          }else if($tema->type == "boleto"){
                                              require_once 'pay/boleto.php';
                                          }else if($tema->type == "fatura"){
                                              $dados_template->$keyTempalte->content = "*Seu Link de Pagamento* \n ".SITE_URL."/".base64_decode($invoiceData->ref);
                                         }
                                      }
                                      
                                      $content_template = json_encode($dados_template);

                                      $dados                = new stdClass();
                                      $dados->assinante_id  = $signature->id;
                                      $dados->client_id     = $client->id;
                                      $dados->content       = $content_template;
                                      $dados->template_id   = $template_message->id;
                                      $dados->instance_id   = $instance->name;
                                      $dados->phone         = $signature->ddi.$signature->whatsapp;
                                      
                                      
                                      /*conecta whatsapp em caso de queda do servidor*/
                                     $curl = curl_init();
                                    
                                     curl_setopt_array($curl, array(
                                        CURLOPT_URL => 'http://whatsapp.'.parse_url(SITE_URL, PHP_URL_HOST).'/session/connect',
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => '',
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 2,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => 'POST',
                                        CURLOPT_POSTFIELDS =>'{"Subscribe":["Message"],"Immediate":false}',
                                        CURLOPT_HTTPHEADER => array(
                                          'Token: '.trim($instance->name),
                                          'Content-Type: application/json'
                                        ),
                                      ));
                                    
                                    $response = curl_exec($curl);
                                    curl_close($curl);

                                    $charges->insertFila($dados);
                                    $charges->insertCharge($dadosInvoice);
                                        
                                }
                                
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
    }

  }


?>
