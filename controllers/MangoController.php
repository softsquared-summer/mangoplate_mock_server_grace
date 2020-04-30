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
         * API No. 3-4
         * API Name : 각 지역 목록
         * 마지막 수정 날짜 : 20.04.30
         */
        case "getDistricts":
            http_response_code(200);

//            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];
//
//            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
//                $res->isSuccess = FALSE;
//                $res->code = 201;
//                $res->message = "유효하지 않은 토큰입니다";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                addErrorLogs($errorLogs, $res, $req);
//                return;
//            }

            $distirctsId = $vars["districts-id"];

            if(!isValidDistrict($distirctsId)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "해당 지역구가 없습니다.";
            }else{
                $res->result = getDistricts($distirctsId);
                $res->isSuccess = TRUE;
                $res->code = 200;
                $res->message = "각 지역 목록 조회";
            }
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;
//        /*
//         * API No. 0
//         * API Name : 테스트 Path Variable API
//         * 마지막 수정 날짜 : 19.04.29
//         */
//        case "testDetail":
//            http_response_code(200);
//            $res->result = testDetail($vars["testNo"]);
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "테스트 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;
//        /*
//         * API No. 0
//         * API Name : 테스트 Body & Insert API
//         * 마지막 수정 날짜 : 19.04.29
//         */
//        case "testPost":
//            http_response_code(200);
//            $res->result = testPost($req->name);
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "테스트 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;
    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
