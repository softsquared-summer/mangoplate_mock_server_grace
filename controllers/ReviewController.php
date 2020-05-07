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

            if ($type == 'main') {
                $typeQuery = ' limit 3;';
            } elseif ($type == 'all') {
                $typeQuery = "";
            } elseif ($type == 'good') {
                $typeQuery = " and rating = 5";
            } elseif ($type == 'okay') {
                $typeQuery = " and rating = 3";
            } elseif ($type == 'bad') {
                $typeQuery = " and rating = 1";
            } else {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Query Params를 확인하세요. (type = main, all, good, okay, bad)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $restaurantId = $vars['restaurantId'];


            $reviewResult = getReviews($restaurantId, $typeQuery);

            if (empty($reviewResult)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Review가 없습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            foreach ($reviewResult as $key => $value) {
                settype($reviewResult[$key]['reviewId'], "integer");
                settype($reviewResult[$key]['userId'], "integer");

                foreach ($reviewResult[$key]['images'] as $imgKey => $imgValue) {
                    settype($reviewResult[$key]['images'][$imgKey]['imageId'], "integer");
                }
            }

            $res->result = $reviewResult;
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "식당 리뷰 목록";
            echo json_encode($res);
            break;

        /*
        * API No. 7-2
        * API Name : 식당 리뷰 추가
        * 마지막 수정 날짜 : 20.05.07
        */
        case "postReview":
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

            // token userId
            $data = getDataByJWToken($jwt, JWT_SECRET_KEY);
            $userEmail = $data->email;
            $userId = getUserId($userEmail);

            // restaurantId
            $restaurantId = $vars['restaurantId'];

            // Body
            $review = $req->review;
            $content = $req->content;
            $imageList = $req->imageList;

            if (empty($review)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Body - review를 입력하세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            $reviewArray = array(1, 3, 5);
            if (!in_array($review, $reviewArray)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Body - review는 5(맛있다), 3(괜찮다), 1(별로)만 가능합니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }


            if (strlen($content) == 0 or strlen($content) > 10000) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Body - content는 1자 이상 10,000자 이내로 입력하세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if (count($imageList) > 30) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Body - imageList는 최대 30개 입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            foreach ($imageList as $key => $value) {
                if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $imageList[$key])) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "올바르지 않은 imageUrl 형식이 있습니다.";
                    echo json_encode($res);
                    return;
                }
                if (!preg_match("/\.(gif|jpg|png)$/i", $imageList[$key])) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "gif, jpg, png가 아닌 image가 있습니다.";
                    echo json_encode($res);
                    return;
                }
            }

            $arr_count = count($imageList);
            $uniq_count = count(array_unique($imageList));
            if ($arr_count != $uniq_count) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "같은 이미지를 여러 번 넣을 수 없습니다.";
                echo json_encode($res);
                return;
            }

            $check = postReview($userId, $restaurantId, $review, $content, $imageList);

            // POST 제대로 됐는지 처리하고 싶은데
            if (isset($check)) {
                $res->isSuccess = FALSE;
                $res->code = 500;
                $res->message = "insert 하지 못했습니다. " . $check;
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }


            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "식당 리뷰 등록 성공";
            echo json_encode($res);
            break;


    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}

