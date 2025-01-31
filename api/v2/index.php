<?php

  /*cobrei.vc*/
  // @AUTHOR: Luan Alves
  // @DATE: 16/05/2021
  // api-whats.com

  header("Content-type: application/json; charset=utf-8");
  date_default_timezone_set('America/Sao_Paulo');

  if(isset($_REQUEST['url'])){

    $url    = explode('/',$_REQUEST['url']);
    $classe = trim(ucfirst($url[0]));
    array_shift($url);
    $metodo = trim($url[0]);
    array_shift($url);

    $params = $_REQUEST;
    $params['rest'] = $url;

    if(is_file("class/{$classe}.class.php")){

      require_once "class/Conn.class.php";
      require_once "class/{$classe}.class.php";

      if(class_exists($classe)){

        if(method_exists($classe,$metodo)){

            try {

              $headers    = getallheaders();
              $class_open = new $classe($headers['Access-token']);
             
              $execute    = $class_open->$metodo($headers,$params);
              
              echo $execute;

            } catch (\Exception $e) {
              echo json_encode(array('status' => 'erro', 'message' => 'Error application'));
            }

        }else{
          echo json_encode(array('status' => 'erro', 'message' => 'Method not exists'));
        }

      }else{
        echo json_encode(array('status' => 'erro', 'message' => 'Method not exists'));
      }

    }else{
      echo json_encode(array('status' => 'erro', 'message' => 'Method not exists'));
    }

  }


?>
