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
         * API No. 1-1
         * API Name : 회원가입 (이메일)
         * 마지막 수정 날짜 : 20.04.30
         */
        case "postUser":
            http_response_code(200);

            $email = $req->email;
            $pw1 = $req->pw1;
            $pw2 = $req->pw2;
            $name = $req->name;
            $profileUrl = $req->profileUrl;
            $phone = $req->phone;;

            // 비었는지
            if (!isset($email) or !isset($pw1) or !isset($pw2) or !isset($name)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "회원가입 실패(사유: email, pw1, pw2, name은 null이 될 수 없습니다.)";
                echo json_encode($res);
                break;
            } else {

                // email Validation
                if (!preg_match("/^[0-9a-zA-Z]([-_.]?[0-9a-zA-Z])*@[0-9a-zA-Z]([-_.]?[0-9a-zA-Z])*.[a-zA-Z]{2,3}$/i", $email)) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "회원가입 실패(사유: email 형식이 올바르지 않습니다.)";
                    echo json_encode($res);
                    break;
                } else {
                    if (isExistUser($email)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "회원가입 실패(사유: 이미 존재하는 email 입니다.)";
                        echo json_encode($res);
                        break;
                    }
                }

                // password
                if ($pw1 != $pw2) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "회원가입 실패(사유: pw1, pw2가 일치하지 않습니다.)";
                    echo json_encode($res);
                    break;
                } else {
                    if (!preg_match("/^[A-Za-z0-9]{6,12}$/", $pw1)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "회원가입 실패(사유: pw은 숫자, 문자를 포함한 6~12자리를 입력하세요.)";
                        echo json_encode($res);
                        break;
                    }
                }

                // name
                $nameLen = mb_strlen($name, 'utf-8');
                if ($nameLen < 2 or $nameLen > 20) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "회원가입 실패(사유: name은 2자 이상 20자 이하로 입력하세요.)";
                    echo json_encode($res);
                    break;
                }
            }

            // phone Validation
            if (isset($phone)) {
                if (!preg_match("/^\d{3}-\d{3,4}-\d{4}$/", $phone)) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "회원가입 실패(사유: phone 형식(010-0000-0000)이 올바르지 않습니다.)";
                    echo json_encode($res);
                    break;
                }
            }

            // profileUrl Validation
            if (isset($profileUrl)) {
                if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $profileUrl)) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "회원가입 실패(사유: profileUrl의 Url 형식이 올바르지 않습니다.)";
                    echo json_encode($res);
                    break;
                }
                if (!preg_match("/\.(gif|jpg|png)$/i", $profileUrl)) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "회원가입 실패(사유: profileUrl은 gif, jpg, png만 가능합니다.)";
                    echo json_encode($res);
                    break;
                }
            }
            $postRes = postUser($email, $pw1, $name, $profileUrl, $phone);
            $res->result = $postRes;
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "회원가입 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
        * API No. 1-2
        * API Name : 로그인 (이메일 O, 카카오 X, 페이스북 O)
        * 마지막 수정 날짜 : 20.05.01
        */
        case "createJwt":
            http_response_code(200);

            $type = $_GET["type"];

            if ($type == 'email') {

                $email = $req->email;
                $pw = $req->pw;

                if (!isset($email) or !isset($pw)) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "email, pw를 입력하세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }

                if (!isValidUser($email, $pw)) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "유효하지 않은 사용자 입니다";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }

                $jwt = getJWToken($email, $pw, JWT_SECRET_KEY);
                $res->result->jwt = $jwt;
                $res->isSuccess = TRUE;
                $res->code = 200;
                $res->message = "로그인 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;

            } elseif ($type == 'kakao') {

                $id = $req->id;
                $name = $req->name;
                $profileUrl = $req->profileUrl;

                if (!isset($id) or !isset($name)) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "id, name을 입력하세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }

//                if (!isValidKakaoUser($id)) {
//                    $res->isSuccess = FALSE;
//                    $res->code = 400;
//                    $res->message = "유효하지 않은 사용자 입니다";
//                    echo json_encode($res, JSON_NUMERIC_CHECK);
//                    return;
//                }
                if (isset($profileUrl)) {
                    if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $profileUrl)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "회원가입 실패(사유: profileUrl의 Url 형식이 올바르지 않습니다.)";
                        echo json_encode($res);
                        break;
                    }
                    if (!preg_match("/\.(gif|jpg|png)$/i", $profileUrl)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "회원가입 실패(사유: profileUrl은 gif, jpg, png만 가능합니다.)";
                        echo json_encode($res);
                        break;
                    }
                }

                $email = $id . "@" . $type;
                // 회원가입 시킬지 말지
                if (!isExistUser($email)) {

                    postUser($email, '', $id, $profileUrl, '');
                    $jwt = getJWToken($email, '', JWT_SECRET_KEY);
                    $res->result->jwt = $jwt;
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "회원가입 및 로그인 성공";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;

                } else {
                    $jwt = getJWToken($email, '', JWT_SECRET_KEY);
                    $res->result->jwt = $jwt;
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "로그인 성공";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }

            } elseif ($type == 'facebook') {

                $at = $req->at;
                if (!isset($at)) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "access token을 입력하세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }

                $userInfo = facebook($at);
                $id = $userInfo->id;
                $name = $userInfo->name;

                if ($userInfo == null) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "유효하지 않은 사용자 입니다";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }

                $email = $id . "@" . $type;


                // 회원가입 시킬지 말지
                if (!isExistUser($email)) {

                    postUser($email, '', $name, '', '');
                    $jwt = getJWToken($email, '', JWT_SECRET_KEY);
                    $res->result->jwt = $jwt;
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "회원가입 및 로그인 성공";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;

                } else {
                    $jwt = getJWToken($email, '', JWT_SECRET_KEY);
                    $res->result->jwt = $jwt;
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "로그인 성공";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }

            } else {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Query Params를 확인하세요. (type = email, kakao, facebook)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            break;

        /*
        * API No. 1-5
        * API Name : 내 정보 조회
        * 마지막 수정 날짜 : 20.05.05
        */

        case "getMe":
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

            $userId = getUserId($userEmail);
            
            $res->result = getMe($userId);
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "내 정보 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
        * API No. 1-6
        * API Name : 내 정보 수정
        * 마지막 수정 날짜 : 20.05.05
        */

        case "patchUser":
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

            $userId = getUserId($userEmail);
            
            // 몇 개 들어왔는지 확인
            $num = 0;
            

            $name = $req->name;
            $profileUrl = $req->profileUrl;
            $phone = $req->phone;

            // 비었는지
            if (!isset($name) and !isset($profileUrl) and !isset($phone)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "업데이트할 요소가 없습니다. (name, profileUrl, phone 중 하나를 입력하세요.)";
                echo json_encode($res);
                return;
            } else {
                if(isset($name)){
                    $num++;
                }
                if(isset($phone)){
                    $num++;
                }
                if(isset($profileUrl)){
                    $num++;
                }

                if($num > 1){
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "name, profileUrl, phone 중 한 가지만 입력하세요.";
                    echo json_encode($res);
                    return;
                }

                if(isset($name)){
                    // name
                    $nameLen = mb_strlen($name, 'utf-8');
                    if ($nameLen < 2 or $nameLen > 20) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "회원가입 실패(사유: name은 2자 이상 20자 이하로 입력하세요.)";
                        echo json_encode($res);
                        break;
                    }



                    $temp = patchUserName($userId, $name);
                    if ($temp == 'false') {
                        $res->isSuccess = FALSE;
                        $res->code = 500;
                        $res->message = "name 업데이트 실패";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "name 업데이트 성공";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }


                // phone Validation
                if (isset($phone)) {
                    if (!preg_match("/^\d{3}-\d{3,4}-\d{4}$/", $phone)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "phone 형식(010-0000-0000)이 올바르지 않습니다.";
                        echo json_encode($res);
                        break;
                    }

                    $temp = patchUserPhone($userId, $phone);

                    if ($temp == 'false') {
                        $res->isSuccess = FALSE;
                        $res->code = 500;
                        $res->message = "phone 업데이트 실패";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "phone 업데이트 성공";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;

                }

                // profileUrl Validation
                if (isset($profileUrl)) {
                    if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $profileUrl)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "profileUrl의 Url 형식이 올바르지 않습니다.";
                        echo json_encode($res);
                        break;
                    }
                    if (!preg_match("/\.(gif|jpg|png)$/i", $profileUrl)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "profileUrl은 gif, jpg, png만 가능합니다.";
                        echo json_encode($res);
                        break;
                    }

                   $temp = patchUserProfileUrl($userId, $profileUrl);

                    if ($temp == 'false') {
                        $res->isSuccess = FALSE;
                        $res->code = 500;
                        $res->message = "profileUrl 업데이트 실패";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "profileUrl 업데이트 성공";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }

            }

            break;

        /*
        * API No. 2-1
        * API Name : 첫 이벤트 조회
        * 마지막 수정 날짜 : 20.05.01
        */
        case "getEvent":
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

            $res->result = getEvent();
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "첫 이벤트 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
        * API No. 2-2
        * API Name : 이벤트 목록 조회 (메인/내정보)
        * 마지막 수정 날짜 : 20.05.01
        */
        case "getEvents":
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

            if($type =='main'){
                $res->result = getEventsMain();
                $res->isSuccess = TRUE;
                $res->code = 200;
                $res->message = "이벤트 목록 조회(메인)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }elseif($type =='detail'){
                $res->result = getEventsDetail();
                $res->isSuccess = TRUE;
                $res->code = 200;
                $res->message = "이벤트 목록 조회(내정보)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }else{
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Query Params를 입력하세요 (type = main, detail)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

       /*
       * API No. 2-3
       * API Name : 이벤트 상세 조회
       * 마지막 수정 날짜 : 20.05.01
       */
        case "getEventById":
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

            $eventId = $vars['eventId'];
            
            if(!isExistEvent($eventId)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "존재하지 않는 이벤트 입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = getEventById($eventId);
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "이벤트 상세 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
        * API No. 3-1
        * API Name : 내근처 지역 목록
        * 마지막 수정 날짜 : 20.05.02
        */
        case "getNear":
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
            $lat = $_GET['lat'];
            $lng = $_GET['lng'];

            if(!isset($lng) or !isset($lat)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Query Params를 입력하세요.(lat = (위도), lng = (경도))";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $result = getNear($lat, $lng);

            if($result == null){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "10km 이내의 지역이 없습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = getNear($lat, $lng);
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "내근처 지역 목록 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;
        /*
        * API No. 3-4
        * API Name : 지역구 목록
        * 마지막 수정 날짜 : 20.04.30
        */
        case "getDistricts":
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

            $res->result = getDistricts();
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "지역구 목록 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
        * API No. 3-5
        * API Name : 지역 목록
        * 마지막 수정 날짜 : 20.04.30
        */
        case "getAreas":
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

            $distirctsId = $vars["districtId"];

            if (!isValidDistrict($distirctsId)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "해당 지역구가 없습니다.";
            } else {

                if (empty($type)) {
                    $res->result = getAreas($distirctsId);
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "지역 목록 조회";
                } elseif ($type == 'eatdeal') {
                    $res->result = getEatdealAreas($distirctsId);
                    $res->isSuccess = TRUE;
                    $res->code = 200;
                    $res->message = "지역 목록 조회 - EAT딜";
                }else{
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "Query params를 확인하세요. (type은 비워져 있거나, eatdeal 만 가능합니다.)";
                }
            }
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
        * API No. 5-1
        * API Name : 추천 검색어 목록
        * 마지막 수정 날짜 : 20.05.04
        */
        case "getKeywords":
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

            $res->result = getKeywords();
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "추천 검색어 목록";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


        /*
        * API No. 13-1
        * API Name : EAT딜 목록
        * 마지막 수정 날짜 : 20.05.04
        */
        case "getEatdeals":
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


            $area = $_GET['area'];

            if(!isset($area)){

            }else{

                // echo $area;
                $realArea = "(";

                $temp = str_replace(" ", "", $area);
                $areaArray = explode(',', $temp);

                $areaIdArray = getAreaId($areaArray);
                // print_r($areaIdArray);
                if ($areaIdArray == null) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "Query Params를 확인하세요. (area = 올바르지 않은 (지역명)이 있습니다.)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }

                foreach ($areaIdArray as $key => $value){
                    $realArea = $realArea . '\''.$areaIdArray[$key].'\'';
                    if ($value === end($areaIdArray)){
                        $realArea = $realArea . ')';
                    }else{
                        $realArea = $realArea . ',';
                    }
                }

                $area = ' where area_id in '. $realArea;
            }

            $res->result = getEatdeals($area);
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "EAT딜 목록";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
        * API No. 13-2
        * API Name : EAT딜 상세 조회
        * 마지막 수정 날짜 : 20.05.04
        */
        case "getEatdealDetail":
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

            $eatdealId = $vars["eatdealId"];

            if(!isExistEatdeal($eatdealId)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "해당 EAT딜 식별자가 없습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            $temp = Array();
            $temp2 = Array();
            $temp3 = Array();
            $temp['img'] = getEatdealImg($eatdealId);
            $temp2 = getEatdeal($eatdealId);
            $temp3 =getEatdealDetail($eatdealId);
            $real = array_merge($temp, $temp2, $temp3);

            $res->result = $real;
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "EAT딜 상세 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
        * API No. 6-1
        * API Name : 추천 검색어 목록
        * 마지막 수정 날짜 : 20.05.05
        */
        case "getImages":
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
            
            $restaurantsId = $vars['restaurantId'];

            $res->result = getImages($restaurantsId);
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "식당 사진 목록";
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
