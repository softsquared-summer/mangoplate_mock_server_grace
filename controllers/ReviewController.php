<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));
try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {

        /*
        * API No. 7-1
        * API Name : 식당 리뷰 목록
        * 마지막 수정 날짜 : 20.05.07
        */
        case "getReviews":
            http_response_code(200);

            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            $type = $_GET['type'];

            if($type == 'main'){
                $typeQuery = ' limit 3;';
            }elseif($type == 'all'){
                $typeQuery = "";
            }elseif($type == 'good'){
                $typeQuery = " and rating = 5";
            }elseif($type == 'okay'){
                $typeQuery = " and rating = 3";
            }elseif($type == 'bad'){
                $typeQuery = " and rating = 1";
            }else{
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Query Params를 확인하세요. (type = main, all, good, okay, bad)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $restaurantId = $vars['restaurantId'];


            $reviewResult = getReviews($restaurantId, $typeQuery);

            if(empty($reviewResult)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Review가 없습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            foreach ($reviewResult as $key => $value){
                settype($reviewResult[$key]['reviewId'], "integer");
                settype($reviewResult[$key]['userId'], "integer");

                foreach ($reviewResult[$key]['images'] as $imgKey => $imgValue){
                    settype($reviewResult[$key]['images'][$imgKey]['imageId'], "integer");
                }
            }

            $res->result = $reviewResult;
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "식당 리뷰 목록";
            echo json_encode($res);
            break;

    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}

