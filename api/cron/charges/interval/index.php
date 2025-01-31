<?php

/*cobrei.vc*/
// @AUTHOR: Luan Alves
// @DATE: 16/05/2023
// api-whats.com

header("Content-type: application/json; charset=utf-8");
date_default_timezone_set('America/Sao_Paulo');

if (isset($_REQUEST['url'])) {

    $url = explode('/', $_REQUEST['url']);
    $client_id = trim($url[0]);

    require_once "../../../../panel/config.php";
    require_once "../../../../panel/class/Conn.class.php";
    require_once "../../../../panel/class/Charges.class.php";
    require_once "../../../../panel/class/Options.class.php";
    require_once "../../../../panel/class/Invoice.class.php";

    $charges = new Charges($client_id);
    $options = new Options($client_id);
    $invoice = new Invoice($client_id);

    // get client 
    $client = $charges->getClient();

    if ($client) {

        // verifica a data de expiração
        if (strtotime('now') > $client->due_date) {
            echo 'expired';
            exit;
        }


        $setting_charge = $options->getOption('setting_charge', true);

        if ($setting_charge) {

            // verifica setting charge
            $setting_charge_interval = $options->getOption('setting_charge_interval', true);

            if ($setting_charge_interval) {

                $setting_charge_interval = json_decode($setting_charge_interval);

                // get signatures
                $signatures = $charges->getSignaturesExpiredAll();

                if ($signatures) {

                    if ($setting_charge_interval->active > 0) {

                        if ($setting_charge_interval->next_date != date('d-m-Y')) {
                            die(json_encode(['success' => false, 'message' => 'not day']));
                        }

                        $setting_charge_interval->next_date = date('d-m-Y', strtotime('+' . $setting_charge_interval->interval_days . ' days'));

                        $options->editOption('setting_charge_interval', json_encode($setting_charge_interval));

                        // verifica whatsapp
                        $instance = $charges->getInstanceByClient();

                        if ($instance) {

                            foreach ($signatures as $key => $signature) {

                                $plan = $charges->getPlanbyId($signature->plan_id);

                                if ($plan) {

                                    $invoiceLasted = $invoice->getInvoiceOpen($signature->id);

                                    // expirate invoice
                                    $expirate_days_invocie = !isset($setting_charge->expire_date_days) ? 7 : (int) $setting_charge->expire_date_days;
                                    $expirate_invoice = strtotime('+' . $expirate_days_invocie . ' days', strtotime('now'));

                                    // create invoice
                                    $dadosInvoice = new stdClass();
                                    $dadosInvoice->id_assinante = $signature->id;
                                    $dadosInvoice->assinante_id = $dadosInvoice->id_assinante;
                                    $dadosInvoice->status = 'pending';
                                    $dadosInvoice->value = $plan->valor;
                                    $dadosInvoice->plan_id = $plan->id;
                                    $dadosInvoice->expire_date = date('Y-m-d H:i:s', $expirate_invoice);
                                    $dadosInvoice->client_id = $client->id;
                                    if ($invoiceLasted == false) {
                                        $invoiceAdd = $invoice->addInvoice($dadosInvoice, true);
                                    } else {
                                        $invoiceAdd = $invoiceLasted->id;
                                    }

                                    $invoiceData = $invoice->getInvoiceByid($invoiceAdd);
                                    $dadosInvoice->invoice_id = $invoiceAdd;

                                    // is max send 
                                    $countSend = $invoice->countSends($invoiceAdd);
                                
                                    if ($countSend && (int)$setting_charge_interval->max_send > 0) {
                                        if ($countSend >= (int) $setting_charge_interval->max_send) {
                                            continue;
                                        }
                                    }

                                    $invoice->sumSends($invoiceAdd);

                                    $template_message = $charges->getTemplateById($plan->template_late);

                                    if ($template_message) {

                                        $dados_template = json_decode($template_message->texto);

                                        foreach ($dados_template as $keyTempalte => $tema) {
                                            if ($tema->type == "pix") {
                                                require_once '../pay/pix.php';
                                            } else if ($tema->type == "boleto") {
                                                require_once '../pay/boleto.php';
                                            } else if ($tema->type == "fatura") {
                                                $dados_template->$keyTempalte->content = "*Seu Link de Pagamento* \n " . SITE_URL . "/" . base64_decode($invoiceData->ref);
                                            }
                                        }

                                        $content_template = json_encode($dados_template);

                                        $dados = new stdClass();
                                        $dados->assinante_id = $signature->id;
                                        $dados->client_id = $client->id;
                                        $dados->content = $content_template;
                                        $dados->template_id = $template_message->id;
                                        $dados->instance_id = $instance->name;
                                        $dados->phone = $signature->ddi . $signature->whatsapp;


                                        /*conecta whatsapp em caso de queda do servidor*/
                                        $curl = curl_init();

                                        curl_setopt_array($curl, array(
                                            CURLOPT_URL => 'http://whatsapp.' . parse_url(SITE_URL, PHP_URL_HOST) . '/session/connect',
                                            CURLOPT_RETURNTRANSFER => true,
                                            CURLOPT_ENCODING => '',
                                            CURLOPT_MAXREDIRS => 10,
                                            CURLOPT_TIMEOUT => 2,
                                            CURLOPT_FOLLOWLOCATION => true,
                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                            CURLOPT_CUSTOMREQUEST => 'POST',
                                            CURLOPT_POSTFIELDS => '{"Subscribe":["Message"],"Immediate":false}',
                                            CURLOPT_HTTPHEADER => array(
                                                'Token: ' . trim($instance->name),
                                                'Content-Type: application/json'
                                            ),
                                        )
                                        );

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

}


?>