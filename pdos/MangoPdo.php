<?php

// 1. 로그인
function isExistUser($email){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM user u WHERE u.email= ?) AS exist;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);
}

function postUser($email, $pw1, $name, $profileUrl, $phone)
{
    if(!isset($profileUrl)){
        $profileUrl = '';
    }
    if(!isset($phone)){
        $phone = '';
    }
    if(!isset($pw1)){
        $pw1 = '';
    }

    $pdo = pdoSqlConnect();
    $query = "INSERT INTO user (email, password, name, profile_url, phone) VALUES (?, ?, ?, ?, ?)";

    $st = $pdo->prepare($query);
    $st->execute([$email, $pw1, $name, $profileUrl, $phone]);

    $userId = $pdo->lastInsertId();

    $st = null;
    $pdo = null;

    $res = (Object)Array();
    $res-> userId = $userId;
    return $res;
    
}

function isExistId($userId){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM user u WHERE u.id= ?) AS exist;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);
}

function getMyInfo($userId){
    $pdo = pdoSqlConnect();
    $userQuery = "select IF(profile_url is null, '', profile_url) profileUrl, name from user where id = ?;";

    $followerQuery="select IF(COUNT(id) is null, 0, COUNT(id))          followerNum
from user
         LEFT JOIN (select user_id, COUNT(*) reviewNum
                    from review
                    group by user_id) REVIEW ON REVIEW.user_id = id
         LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                    from friend
                    group by friend_id) FOLLOWER_NUM ON FOLLOWER_NUM.friend_id = id
         JOIN (select user_id
               from friend
               where friend_id = ?) FOLLOWING ON FOLLOWING.user_id = id;";

    $followingQuery="select IF(COUNT(id) is null, 0, COUNT(id)) followingNum
from user
         LEFT JOIN (select user_id, COUNT(*) reviewNum
                    from review
                    group by user_id) REVIEW ON REVIEW.user_id = id
         LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                    from friend
                    group by friend_id) FOLLOWER_NUM ON FOLLOWER_NUM.friend_id = id
         JOIN (select friend_id
               from friend
               where user_id = ?) FOLLOWER ON FOLLOWER.friend_id = id;";

    $reviewQuery="select IF(COUNT(id) is null, 0, COUNT(id)) reviewNum from review where user_id=?;";

    $photoQuery="select IF(COUNT(*) is null, 0, COUNT(*)) photoNum
from restaurant_image
where review_id in (select id reviewNum from review where user_id = ?);";

    $futureQuery = "select IF(COUNT(*) is null, 0, COUNT(*)) futureNum
from future
where user_id = ?;";

    $res = Array();

    $st = $pdo->prepare($userQuery);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $userRes = $st->fetch();

    $st = $pdo->prepare($followerQuery);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $followerRes = $st->fetch();

    $st = $pdo->prepare($followingQuery);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $followingRes = $st->fetch();

    $st = $pdo->prepare($reviewQuery);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $reviewRes = $st->fetch();

    $st = $pdo->prepare($photoQuery);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $photoRes = $st->fetch();

    $st = $pdo->prepare($futureQuery);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $futureRes = $st->fetch();

    $real = array_merge($userRes, $followerRes, $followingRes, $reviewRes, $photoRes, $futureRes);
//    print_r($res);
    return $real;
}

function getMe($userId){
    $pdo = pdoSqlConnect();
    $query = "select profile_url profileUrl,
       name,
       email,
       phone
from user
where id=?;";

    $st = $pdo->prepare($query);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return $res[0];
}
//function patchUserEmail($userId, $email){
//    $pdo = pdoSqlConnect();
//    try {
//        $query = "UPDATE user
//SET user.email = ?
//WHERE id = ?;";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$email, $userId]);
//    } catch (Exception $e) {
//        return 'false';
//    }
//
//    $st=null;
//    $pdo = null;
//}
function patchUserName($userId, $name){
    $pdo = pdoSqlConnect();

    try {
        $query = "UPDATE user
SET user.name = ?
WHERE id = ?;";

        $st = $pdo->prepare($query);
        $st->execute([$name, $userId]);
    } catch (Exception $e) {
        return 'false';
    }
    $st = null;
    $pdo = null;
}

function patchUserProfileUrl($userId, $profileUrl){
    $pdo = pdoSqlConnect();
    try{
        $query = "UPDATE user
SET user.profile_url = ?
WHERE id = ?;";

        $st = $pdo->prepare($query);
        $st->execute([$profileUrl, $userId]);
    }catch (Exception $e){
        return 'false';
    }

    $st=null;
    $pdo = null;
}

function patchUserPhone($userId, $phone){
    $pdo = pdoSqlConnect();
    try {
        $query = "UPDATE user
SET user.phone = ?
WHERE id = ?;";

        $st = $pdo->prepare($query);
        $st->execute([$phone, $userId]);

    }catch (Exception $e){
        return 'false';
    }

    $st=null;
    $pdo = null;
}
//function getUserId()
//{
//    $pdo = pdoSqlConnect();
//    $query = "select d.id distinctsId, d.name from district d";
//
//    $st = $pdo->prepare($query);
//    $st->execute();
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//
//    $st = null;
//    $pdo = null;
//
//    return $res;
//}
function isValidUser($email, $pw)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM user u WHERE u.email= ? AND u.password = ?) AS exist;";

    $st = $pdo->prepare($query);
    $st->execute([$email, $pw]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]["exist"]);

}

// 2. 이벤트
function getEvent()
{
    $pdo = pdoSqlConnect();
    $query = "select e.id eventId, e.detail_image_url imageUrl
from event e
where e.is_main = 'Y';";

    $st = $pdo->prepare($query);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

function getEventsMain()
{
    $pdo = pdoSqlConnect();
    $query = "select e.id eventId, e.image_url imageUrl
from event e
where (TIMESTAMPDIFF(minute,  CURRENT_TIME, e.end_date) > 0) or e.end_date is null
order by e.end_date;";

    $st = $pdo->prepare($query);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function getEventsDetail()
{
    $pdo = pdoSqlConnect();
    $query = "select e.id        eventId,
       e.image_url imageUrl,
       e.title,
       CASE
           WHEN (TIMESTAMPDIFF(minute,  CURRENT_TIME, e.end_date) < 0) THEN '종료'
           END as status,
       CASE
       WHEN (end_date is null) THEN '기한없음'
       WHEN (TIMESTAMPDIFF(minute,  CURRENT_TIME, e.end_date) < 0) THEN CONCAT(date_format(e.start_date,  '%Y.%c.%e ~ '), date_format(e.end_date,  '%Y.%c.%e'))
       ELSE CONCAT(TIMESTAMPDIFF(day, CURRENT_TIME, e.end_date),'일 남음' ) END as date
from event e
ORDER BY status, date desc;";

    $st = $pdo->prepare($query);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function isExistEvent($eventId){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM event e WHERE e.id= ?) AS exist;";


    $st = $pdo->prepare($query);
    $st->execute([$eventId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);
}

function getEventById($eventId)
{
    $pdo = pdoSqlConnect();
    $query = "select e.detail_image_url imageUrl
from event e
where e.id =?;";

    $st = $pdo->prepare($query);
    $st->execute([$eventId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

// 3. 지역
function getNear($lat, $lng)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT a.district_id districtId,
       a.id areaId,
       a.name,
       CONCAT(	ROUND(6371*acos(cos(radians(?))*cos(radians(a.lat))*cos(radians(a.lng)
	-radians(?))+sin(radians(?))*sin(radians(a.lat))), 2), 'km')
	AS distance
FROM area a
HAVING distance <= 10.0
ORDER BY distance;";

    $st = $pdo->prepare($query);
    $st->execute([$lat, $lng, $lat]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function getDistricts()
{
    $pdo = pdoSqlConnect();
    $query = "select d.id distinctsId, d.name from district d";

    $st = $pdo->prepare($query);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function isValidDistrict($distirctsId){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM district WHERE district.id= ?) AS exist;";


    $st = $pdo->prepare($query);
    $st->execute([$distirctsId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);

}

function getAreas($distirctsId)
{
    $pdo = pdoSqlConnect();
    $query = "select a.id, a.name
from area a
where a.district_id = ?
order by a.name asc;";

    $st = $pdo->prepare($query);
    $st->execute([$distirctsId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function getEatdealAreas($distirctsId)
{
    $pdo = pdoSqlConnect();
    $query = "select id, name
from area
         RIGHT JOIN (select RES.area_id
                     from eatdeal
                              LEFT JOIN (select id restaurant_id, area_id from restaurant) RES
                                        ON RES.restaurant_id = eatdeal.restaurant_id
                     group by RES.area_id) AREA ON AREA.area_id = area.id
where district_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$distirctsId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}



// 5. 검색어
function getKeywords()
{
    $pdo = pdoSqlConnect();
    $query = "select keyword
from keyword
         JOIN (select COUNT(*) cnt, keyword kw
               from keyword
               group by kw
               order by cnt desc
               limit 6) CNT ON CNT.kw = keyword.keyword
group by keyword;";

    $st = $pdo->prepare($query);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 13. EAT 딜
function getEatdeals($area)
{
    $pdo = pdoSqlConnect();
    $query = "select eatdeal.id                                                                    eatdealId,
       AREA.area_id areaId,
       IMG.image_url imageUrl,
       status,
       CONCAT(ROUND(((original_price - sale_price) / original_price) * 100, 0), '%') percent,
       FORMAT(original_price, 0)                                                     originalPrice,
       FORMAT(sale_price, 0)                                                         salePrice,
       title,
       item,
       IF(DES.description is null, '', DES.description) description,
       CASE
           WHEN (quantity = 0) THEN '모두 판매되었습니다.'
           WHEN (quantity != 0 and quantity < 10) THEN CONCAT(quantity, '개 남음') 
           WHEN (quantity >= 10) THEN '' END  quantity
from eatdeal
         JOIN (select eatdeal_id, description from eatdeal_detail) DES ON DES.eatdeal_id = id
         JOIN (select eatdeal_id, image_url
               from eatdeal_image
               group by eatdeal_id) IMG ON IMG.eatdeal_id = id
         JOIN (select eatdeal.id, RES.area_id
               from eatdeal
                        LEFT JOIN (select id restaurant_id, area_id from restaurant) RES
                                  ON RES.restaurant_id = eatdeal.restaurant_id) AREA ON AREA.id = DES.eatdeal_id";

    $query = $query . $area;
//     echo $query;
    $st = $pdo->prepare($query);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    foreach ($res as $key => $value) {
//        unset($res[$key]['area_id']);
//        echo getType($res[$key]);


        if($res[$key]['quantity'] == '모두 판매되었습니다.'){

            unset($res[$key]['percent']);
            unset($res[$key]['originalPrice']);
            unset($res[$key]['salePrice']);
            unset($res[$key]['status']);
        }

    }


    $st = null;
    $pdo = null;

    return $res;
}

function isExistEatdeal($eatdealId){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM eatdeal e WHERE e.id= ?) AS exist;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$eatdealId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);
}

function getEatdeal($eatdealId)
{
    $pdo = pdoSqlConnect();
    $query = "select restaurant_id restaurantId,
CONCAT(item, ' ', percent, ' 할인')                                                     tag,
       title,
       item,
       IF(start_at is null, CONCAT('93일 (', date_format(NOW(), '%Y-%m-%d'), ' ~ ',
                                   date_format(DATE_ADD(NOW(), INTERVAL 92 DAY), '%Y-%m-%d'), ')'),
          CONCAT(date_format(start_at, '%Y-%m-%d'), ' ~ ', date_format(end_at, '%Y-%m-%d'))) term,
       PERCENT.percent,
       FORMAT(original_price, 0)                                                             originalPrice,
       FORMAT(sale_price, 0)                                                                 salePrice

from eatdeal
         JOIN(select id,
                     CONCAT(ROUND(((original_price - sale_price) / original_price) * 100, 0), '%') percent
              from eatdeal) PERCENT ON PERCENT.id = eatdeal.id
where eatdeal.id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$eatdealId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

function getEatdealImg($eatdealId)
{
    $pdo = pdoSqlConnect();
    $query = "select num, image_url imageUrl
from eatdeal_image
where eatdeal_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$eatdealId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    // print_r($res);

    return $res;
}

function getEatdealDetail($eatdealId)
{
    $pdo = pdoSqlConnect();
    $query = "select description,
       place_info place,
       restaurant_info restaurant,
       menu_info menu,
       note_info note
from eatdeal_detail
where eatdeal_id =?;";

    $st = $pdo->prepare($query);
    $st->execute([$eatdealId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $res[0]['benefit']='BC카드로 EAT딜 결제 시, 결제금액의 5% 추가 할인 (청구할인)
페이북 또는 BC카드 앱 내 마이태그 이용 시, 결제금액의 5% 추가 할인 (청구 할인)
익월 20일경 청구할인 예정이며, 청구 할인된 내용은 익월 또는 익익월 명세서를 통해 확인 가능합니다.';
    $res[0]['how']='구매하신 EAT딜은 최신 버전 앱에서만 사용 가능합니다.
결제 시 망고플레이트 앱 > 내정보 > 구매한 EAT딜을 선택하여 매장에 비치된 QR코드를 스캔합니다.
QR코드 스캔이 불가능할 시 매장 직원에게 화면 하단 12자리 숫자 코드를 보여주세요.
사용 처리가 완료된 EAT딜은 재사용 및 환불 불가합니다.';
    $res[0]['refund'] = '상품 사용 기간 내 환불 요청에 한해 구매 금액 전액 환불, 상품 사용 기간 이후 환불 요청 건은 수수료 10%를 제외한 금액 환불을 원칙으로 합니다.
환불 기간 연장은 불가합니다.
구매 후 93일 이내 환불 요청: 100% 환불
구매 후 93일 이후 환불 요청: 90% 환불
환불은 구매 시 사용하였던 결제수단으로 환불됩니다.';
    $res[0]['inquiry']='cs@mangoplate.com';
    $st = null;
    $pdo = null;

    return $res[0];
}

// 6. 사진
function getImages($restaurantsId)
{
    $pdo = pdoSqlConnect();
    $query = "select id imageId,
       image_url imageUrl
from restaurant_image
         RIGHT JOIN (select id reviewId, created_at
                     from review
                     where restaurant_id = ?) REVIEW ON REVIEW.reviewId = review_id
where image_url is not null
order by created_at desc;";

    $st = $pdo->prepare($query);
    $st->execute([$restaurantsId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// 11. 가고싶다

function isExistFuture($userId, $restaurantId){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM future f WHERE f.user_id=? and f.restaurant_id=?) AS exist;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$userId, $restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);
}

function postFuture($userId, $restaurantId, $status){
    $pdo = pdoSqlConnect();
    $insertQuery = "INSERT INTO future (user_id, restaurant_id, state) VALUES (?, ?, 'Y');";
    $updateQuery = "UPDATE future SET state = IF(state = 'Y', 'N', 'Y')  WHERE user_id =? and restaurant_id =?;";

    $query = "";

    if($status == 'insert'){
        $query = $insertQuery;
    }elseif($status == 'update'){
        $query = $updateQuery;
    }

    $st = $pdo->prepare($query);
    $st->execute([$userId, $restaurantId]);

    $st = null;
    $pdo = null;

}


function userFutureStatus($userId, $restaurantId){

    $pdo = pdoSqlConnect();
    $query = "select IF(state = 'N', 'NO', 'YES') state
from future
where user_id =? and restaurant_id = ?";

    $st = $pdo->prepare($query);
    $st->execute([$userId, $restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];

}

function getFutures($lat, $lng, $myId, $otherId, $area, $kind, $price, $order, $parking)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT area_id                                           areaId,
       AREA.a_name                                       area,
       id                                                restaurantId,
       image_url                                         img,
       IF(FUTURE.star is null, 'NO', star)               star,
       name                                              title,
       IF(SEEN.seenNum is null,
          0, seenNum)                                    seenNum,
       REVIEW.reviewNum,
       RATING.rating,
       CASE
           WHEN (REVIEW.reviewNum = 0) THEN null
           WHEN (REVIEW.reviewNum <= 3) THEN 'gray'
           WHEN (REVIEW.reviewNum > 3) THEN 'orange' END ratingColor
FROM restaurant
         LEFT JOIN (select *
                    from rating) RATING ON RATING.restaurant_id = id
         LEFT JOIN (select restaurant_id,
                           FORMAT(num,
                                  0) seenNum
                    from seen) SEEN ON SEEN.restaurant_id = id
         LEFT JOIN (select restaurant_id,
                           IF(state = 'Y', 'YES', 'NO') star,
                           created_at                   myCreatedAt
                    from future
                    where user_id = ?) FUTURE ON FUTURE.restaurant_id = id
         LEFT JOIN (select restaurant_id, COUNT(*) reviewNum
                    from review
                    group by restaurant_id) REVIEW ON REVIEW.restaurant_id = id
         LEFT JOIN (select *
                    from (select rv.restaurant_id, REIMG.image_url, rv.created_at
                          from review rv
                                   LEFT JOIN (select *
                                              from restaurant_image
                                              group by review_id) REIMG ON REIMG.review_id = rv.id
                          where image_url is not null
                          order by restaurant_id, created_at asc
                          LIMIT 18446744073709551615) as a
                    group by a.restaurant_id) IMG ON IMG.restaurant_id = id
         JOIN(select a.id a_id, a.name a_name from area a) AREA ON AREA.a_id = restaurant.area_id
         JOIN (select restaurant.id rId,
                      ROUND(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng)
                          - radians($lng)) + sin(radians($lat)) * sin(radians(lat))),
                            2)      dist
               from restaurant) DIST ON DIST.rId = restaurant.id
        LEFT JOIN (select restaurant_id, price, parking, kind from information) INFO ON INFO.restaurant_id = id
         RIGHT JOIN (select fu.restaurant_id                userFutureRES,
                            IF(fu.state = 'Y', 'YES', 'NO') userStar,
                            fu.created_at                   userCreatedAt
                     from future fu
                     where fu.user_id = ?) USERFUTURE ON USERFUTURE.userFutureRES = restaurant.id";

    $filter = "";

    if($myId == $otherId){
        $filter = " where userStar = 'YES' and ";
    }elseif($myId != $otherId){
//        $filter = " where ";
        $filter = " where ";
    }
//    echo '  ' . $filter;
//    $filter = $temp;

//    $filter = " where ";
    if(isset($area)){
        $filter = $filter . $area . " and ";
    }
    if (isset($kind)) {
        $filter = $filter . $kind . " and ";
    }
    if (isset($price)) {
        $filter = $filter . $price . " and ";
    }
//    if (isset($radius)) {
//        $filter = $filter . $radius . " and ";
//    }
//    if (isset($category)) {
//        $filter = $filter . $category . " and ";
//    }
    if (isset($parking)) {
        $filter = $filter . $parking . " and ";
    }
//    echo '  ' . $filter;

    if($filter == " where "){
        $filter = "";
    }else{
        $filter = substr($filter, 0, -4);
    }

//    echo '  ' . $filter;
//    $filter = substr($filter, 0, -4);
    /* if (isset($kind)) {
         $filter = $filter . " and " . $kind;
     }
     if (isset($price)) {
         $filter = $filter . " and " . $price ;
     }
     if (isset($radius)) {
         $filter = $filter . " and " . $radius ;
     }
     if (isset($category)) {
         $filter = $filter . " and " . $category;
     }
     if (isset($parking)) {
         $filter = $filter . " and " . $parking;
     }*/


//    echo $filter;
    $filter = $filter . " " . $order . ";";

    $query = $query . $filter;

//     echo $query;

    $st = $pdo->prepare($query);
    $st->execute([$myId, $otherId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    // title 앞에 번호 붙이기
//    foreach ($res as $key => $value) {
//
//        $res[$key]['title'] = ($key + 1) . ". " . $res[$key]['title'];
//
//    }

    $st = null;
    $pdo = null;

    return $res;
}



//
////READ
//function testDetail($testNo)
//{
//    $pdo = pdoSqlConnect();
//    $query = "SELECT * FROM Test WHERE no = ?;";
//
//    $st = $pdo->prepare($query);
//    $st->execute([$testNo]);
//    //    $st->execute();
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//
//    $st = null;
//    $pdo = null;
//
//    return $res[0];
//}
//
//
//function testPost($name)
//{
//    $pdo = pdoSqlConnect();
//    $query = "INSERT INTO Test (name) VALUES (?);";
//
//    $st = $pdo->prepare($query);
//    $st->execute([$name]);
//
//    $st = null;
//    $pdo = null;
//
//}
//
//
//function isValidUser($id, $pw)
//{
//    $pdo = pdoSqlConnect();
//    $query = "SELECT EXISTS(SELECT * FROM User WHERE userId= ? AND userPw = ?) AS exist;";
//
//
//    $st = $pdo->prepare($query);
//    //    $st->execute([$param,$param]);
//    $st->execute([$id, $pw]);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//
//    $st = null;
//    $pdo = null;
//
//    return intval($res[0]["exist"]);
//
//}
//

// CREATE
//    function addMaintenance($message){
//        $pdo = pdoSqlConnect();
//        $query = "INSERT INTO MAINTENANCE (MESSAGE) VALUES (?);";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message]);
//
//        $st = null;
//        $pdo = null;
//
//    }


// UPDATE
//    function updateMaintenanceStatus($message, $status, $no){
//        $pdo = pdoSqlConnect();
//        $query = "UPDATE MAINTENANCE
//                        SET MESSAGE = ?,
//                            STATUS  = ?
//                        WHERE NO = ?";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message, $status, $no]);
//        $st = null;
//        $pdo = null;
//    }

// RETURN BOOLEAN
//    function isRedundantEmail($email){
//        $pdo = pdoSqlConnect();
//        $query = "SELECT EXISTS(SELECT * FROM USER_TB WHERE EMAIL= ?) AS exist;";
//
//
//        $st = $pdo->prepare($query);
//        //    $st->execute([$param,$param]);
//        $st->execute([$email]);
//        $st->setFetchMode(PDO::FETCH_ASSOC);
//        $res = $st->fetchAll();
//
//        $st=null;$pdo = null;
//
//        return intval($res[0]["exist"]);
//
//    }
