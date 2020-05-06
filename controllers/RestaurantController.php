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
        * API No. 4-1
        * API Name : 식당 목록 (추천순)
        * 마지막 수정 날짜 : 20.05.03
        */
        case "getRestaurants":
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
            // echo $userId;

//            echo $area;
//            $area = str_replace(" ", "", $area);
//            echo $area;
//            $myArray = explode(',', $area);
//
//            print_r($myArray);
//
//            echo $myArray[0];


//            $query = "SELECT EXISTS(SELECT * FROM user u WHERE u.email= ?) AS exist;";
//            $value = "가나다라마바사";
//
//            $query1 = str_replace("u.email=", "u.name=", $query);
//            echo $query1;

            $lat = $_GET['lat'];
            $lng = $_GET['lng'];
            $type = $_GET['type'];

            if (!isset($lng) or !isset($lat)) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Query Params를 입력하세요.(lat = (위도), lng = (경도))";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            if (!($type == 'main') and !($type == 'map')) {
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "Query Params를 확인하세요. (type = main, map)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            



            //main인지 map인지만 따져서 함수 2개 각각.

            $isNear = false;
            $area = $_GET['area'];

            // $area 받는 즉시 $radius 체크하기. 나중에 $area 없으면 값을 가까운 값으로 넣는단 말이지.
            $radius = $_GET['radius'];
            if(!isset($radius)){

                if(!isset($area)){
                    // echo "이게 나와야 해";
                    // $area X $radius X 일 때
                    $radius = 'DIST.dist < 3';
                }
            }else{
                if (isset($area)) {
//                    if(!$isNear){
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "Query Params를 확인하세요.(area가 있을 때는 radius에 값을 할당할 수 없습니다.)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
//                    }
                }else{
                    if($radius == '0.5'){
                        $radius = 'DIST.dist < 0.5';
                    }elseif($radius == '1'){
                        $radius ='DIST.dist < 1';
                    }
                    elseif($radius == '3'){
                        $radius ='DIST.dist < 3';
                    }else{
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "Query Params를 확인하세요.(radius 값은 0.5, 1, 3 입니다.)";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }



            // 키워드
            $keyword = $_GET['keyword'];




            if(!isset($area)){

                if(!isset($keyword)){
                    // 여기는 area 설정 안되어 있을 경우에, 자신과 가장 가까운 지역을 넣는건데,
                    // 만약, keyword가 포함되어 있으면 area에 아무것도 넣지마

                    $result = getNear($lat, $lng);


                    $nearestAreaId = $result[0]['areaId'];
                    $nearestAreaName = $result[0]['name'];
                    $area = 'AREA.a_name in (\'' . $nearestAreaName . '\')';

                    $isNear = true;
                }

            }else{

                $realArea = "(";

                $temp = str_replace(" ", "", $area);
                $areaArray = explode(',', $temp);

                $areaIdArray = getAreaId($areaArray);
                if ($areaIdArray == null) {
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "Query Params를 확인하세요. (area = 올바르지 않은 (지역명)이 있습니다.)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }

                foreach ($areaArray as $key => $value){
                    $realArea = $realArea . '\''.$areaArray[$key].'\'';
                    if ($value === end($areaArray)){
                        $realArea = $realArea . ')';
                    }else{
                        $realArea = $realArea . ',';
                    }
                }

                $area = 'AREA.a_name in '.$realArea;

            }

            $kind = $_GET['kind'];
            if(!isset($kind)){

            }else{

                $realKind = "(";

                $temp = str_replace(" ", "", $kind);
                $kindArray = explode(',', $temp);

                $kindValue = array('한식', '일식', '중식', '양식', '세계음식', '뷔페', '카페', '주점');
                foreach ($kindArray as $key => $value){

                    $validPrice = $kindArray[$key];
                    if (!in_array($validPrice, $kindValue)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "Query Params를 확인하세요.(kind 값은 한식, 일식, 중식, 양식, 세계음식, 뷔페, 카페, 주점 입니다.)";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }

                    $realKind = $realKind . '\''.$kindArray[$key].'\'';
                    if ($value === end($kindArray)){
                        $realKind = $realKind . ')';
                    }else{
                        $realKind = $realKind . ',';
                    }
                }

                $kind = 'kind in '.$realKind;

                // echo $realKind;

            }

            $price = $_GET['price'];
            if(!isset($price)){

            }else{


                $realPrice = "(";

                $temp = str_replace(" ", "", $price);
                $priceArray = explode(',', $temp);


                // 유효성 검사
                $priceValue = array('0', '1', '2', '3');

                foreach ($priceArray as $key => $value){

                    $validPrice = $priceArray[$key];
                    if (!in_array($validPrice, $priceValue)) {
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "Query Params를 확인하세요.(price 값은 0, 1, 2, 3 입니다.)";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }


                    $realPrice = $realPrice . '\''.$priceArray[$key].'\'';
                    if ($value === end($priceArray)){
                        $realPrice = $realPrice . ')';
                    }else{
                        $realPrice = $realPrice . ',';
                    }
                }

                $price = 'price in '.$realPrice;
            }


           /* $radius = $_GET['radius'];
//            echo gettype($radius);


            echo $radius;
            if(!isset($radius)){
//                $radius = ''
//                echo "test";
                if(!isset($area)){
                    echo "이게 나와야 해";
                    // $area X $radius X 일 때
                    $radius = 'DIST.dist < 3';
                }
            }else{
                if(isset($area)){
                    if(!$isNear){
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "Query Params를 확인하세요.(area가 있을 때는 radius에 값을 할당할 수 없습니다.)";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        break;
                    }
                }else{
                    if($radius == '0.5'){
                        $radius = 'DIST.dist < 0.5';
                    }elseif($radius == '1'){
                        $radius ='DIST.dist < 1';
                    }
                    elseif($radius == '3'){
                        $radius ='DIST.dist < 3';
                    }else{
                        $res->isSuccess = FALSE;
                        $res->code = 400;
                        $res->message = "Query Params를 확인하세요.(radius 값은 0.5, 1, 3 입니다.)";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        break;
                    }
                }
            }*/


            $order = $_GET['order'];
            if(!isset($order)){
                $order = 'order by rating desc';
            }else{
                if($order=='평점순'){
                    $order = 'order by rating desc';
                }elseif($order=='리뷰순'){
                    $order = 'order by reviewNum desc';
                }elseif ($order=='거리순'){
                    $order = 'order by dist';
                }else{
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "Query Params를 확인하세요.(order는 평점순 / 리뷰순 / 거리순 중 하나 입니다.)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
            }


            $category = $_GET['category'];
            if(!isset($category)){

            }else{
                if($category=='가고싶다'){
                    $category='star = \'YES\'';
                }elseif($category=='전체'){
                    $category=null;
                }else{
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "Query Params를 확인하세요.(category는 전체 / 가고싶다 중 하나 입니다.)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
            }


            $parking = $_GET['parking'];
            if(!isset($parking)){

            }else{
                if($parking=='가능'){
                    $parking='parking is not null';
                }elseif($parking=='상관없음'){
                    $parking=null;
                }else{
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "Query Params를 확인하세요.(parking은 상관없음 /가능 중 하나 입니다.)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
            }


            // paging 처리하는 것 받기
            $page = $_GET['page'];
            $parking = $_GET['per'];



            if($type == 'main'){

                $restaurants = getRestaurants($lat, $lng, $userId, $area, $kind, $price, $radius, $order, $category, $parking, $keyword);

                if(empty($restaurants)){
                    $res->isSuccess = FALSE;
                    $res->code = 400;
                    $res->message = "등록된 식당이 없습니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }

                // 콤마 때문에 numeric_check 지우고 int값 필요한 것들은 변환해줬음.
                foreach ($restaurants as $key => $value){
                    settype($restaurants[$key]['areaId'], "integer");
                    settype($restaurants[$key]['restaurantId'], "integer");
                }

                $res->result = $restaurants;
                $res->isSuccess = TRUE;
                $res->code = 200;
                $res->message = "식당 목록 조회";

                echo json_encode($res);
                break;

            }elseif($type == 'map'){

            }else{

            }
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;



        case "getRestaurant":
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


            $restaurantId = $vars['restaurantId'];


            if(!isExistRestaurant($restaurantId)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "해당 식당 식별자가 없습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            $temp = Array();
            $temp2 = Array();
            $temp3 = Array();
            $temp4 = Array();
            $temp5 = Array();

            $temp['images'] = getRestaurantImages($restaurantId);
            $temp2 = getRestaurant($userId, $restaurantId);
            $temp3['keywords'] = getRestaurantKeywords($restaurantId);
            $temp4['menu'] = getRestaurantMenu($restaurantId);
            $temp5= getMenuUpdate($restaurantId);



            if($temp2 == null){
                $res->isSuccess = FALSE;
                $res->code = 500;
                $res->message = "UPDATE | SELECT 트랜젝션에 실패하였습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($temp4['menu'])){
                $real = array_merge($temp, $temp2, $temp3);
            }else{
                $real = array_merge($temp, $temp2, $temp3, $temp4, $temp5);
            }



            foreach ($real['images'] as $key => $value){
                settype($real['images'][$key]['imageId'], "integer");
            }
/*            foreach ($real as $key => $value){
                // settype($real[$key]['images'], "integer");

                foreach ($real[$key]['images'] as $key2 => $value2){

                    settype($real[$key]['images'][$key2]['imageId'], "integer");
                }

            }*/


            $res->result = $real;
            $res->isSuccess = TRUE;
            $res->code = 200;
            $res->message = "식당 상세 조회";
            echo json_encode($res);
            break;


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
