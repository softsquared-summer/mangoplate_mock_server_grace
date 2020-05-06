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
        * API No. 12-1
        * API Name : 특정 user 팔로워 목록
        * 마지막 수정 날짜 : 20.05.06
        */
        case "getfollower":
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

            $data = getDataByJWToken($jwt, JWT_SECRET_KEY);
            $userEmail = $data->email;

            $myId = getUserId($userEmail);

            $otherId = $vars['userId'];

            // otherId 존재하는 건지 check
            if (!isExistUserId($otherId)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "존재하지 않는 userId 입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $follower = getfollower($myId, $otherId);

            if (empty($follower)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "팔로워 목록이 없습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = $follower;
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "특정 user 팔로워 목록";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


        /*
        * API No. 12-2
        * API Name : 특정 user 팔로잉 목록
        * 마지막 수정 날짜 : 20.05.06
        */
        case "getfollowing":
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

            $data = getDataByJWToken($jwt, JWT_SECRET_KEY);
            $userEmail = $data->email;

            $myId = getUserId($userEmail);

            $otherId = $vars['userId'];

            // otherId 존재하는 건지 check
            if (!isExistUserId($otherId)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "존재하지 않는 userId 입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $following = getfollowing($myId, $otherId);

            if (empty($following)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "팔로잉 목록이 없습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = $following;
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "특정 user 팔로잉 목록";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
