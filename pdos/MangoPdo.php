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

// 4. 식당
function getUserId($userEmail){
    $pdo = pdoSqlConnect();
    $query = "select id from user where email = ?";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0]['id'];
}

function getAreaId($areaArray)
{
    $areaIdArray = Array();
    $pdo = pdoSqlConnect();
    $query = "select a.id
from area a
where a.name =?;";

    $st = $pdo->prepare($query);
    foreach ($areaArray as $key => $value) {
        $st->execute([$value]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();
        if (isset($res[0]['id'])) {
            $areaIdArray[$key] = $res[0]['id'];
        } else {
            return null;
        }
    }

    $st = null;
    $pdo = null;

//    print_r($areaIdArray);
    return $areaIdArray;
}

function getNearRestaurants($lat, $lng, $userId, $nearestAreaId)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT area_id                                           areaId,
       AREA.a_name                                       area,
       id                                                restaurantId,
       image_url                                         img,
       IF(FUTURE.star is null, 'NO', star)               star,
       name                                              title,
       CONCAT(DIST.dist, 'km')                           distance,
       IF(SEEN.seenNum is null, 0, seenNum)              seenNum,
       REVIEW.reviewNum,
       RATING.rating,
       CASE
           WHEN (REVIEW.reviewNum = 0) THEN null
           WHEN (REVIEW.reviewNum <= 3) THEN 'gray'
           WHEN (REVIEW.reviewNum > 3) THEN 'orange' END ratingColor
FROM restaurant
         LEFT JOIN (select *
                    from rating) RATING ON RATING.restaurant_id = id
         LEFT JOIN (select restaurant_id, num seenNum
                    from seen) SEEN ON SEEN.restaurant_id = id
         LEFT JOIN (select restaurant_id,
                           IF(state = 'Y', 'YES', 'NO') star
                    from future
                    where user_id = ?) FUTURE ON FUTURE.restaurant_id = id
         LEFT JOIN (select restaurant_id, COUNT(*) reviewNum
                    from review
                    group by restaurant_id) REVIEW ON REVIEW.restaurant_id = id
         LEFT JOIN (select *
                    from (select rv.restaurant_id, REIMG.image_url, rv.created_at
                          from review rv
                                   LEFT JOIN (select *
                                              from review_image
                                              group by review_id) REIMG ON REIMG.review_id = rv.id
                          where image_url is not null
                          order by restaurant_id, created_at asc
                          LIMIT 18446744073709551615) as a
                    group by a.restaurant_id) IMG ON IMG.restaurant_id = id
         JOIN(select a.id a_id, a.name a_name from area a) AREA ON AREA.a_id = restaurant.area_id
         JOIN (select restaurant.id                                                                 rId,
                      ROUND(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng)
                          - radians($lng)) + sin(radians($lat)) * sin(radians(lat))), 2) dist
               from restaurant) DIST ON DIST.rId = restaurant.id
where area_id = ?
  and DIST.dist < 3
order by rating desc;";


    $st = $pdo->prepare($query);
    $st->execute([$userId, $nearestAreaId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    // title 앞에 번호 붙이기
    foreach ($res as $key => $value) {

        $res[$key]['title'] = ($key + 1) . ". " . $res[$key]['title'];
        
    }

    $st = null;
    $pdo = null;

    return $res;
}


function getRestaurants($lat, $lng, $userId, $area, $kind, $price, $radius, $order, $category, $parking)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT area_id                                           areaId,
       AREA.a_name                                       area,
       id                                                restaurantId,
       image_url                                         img,
       IF(FUTURE.star is null, 'NO', star)               star,
       name                                              title,
       CONCAT(DIST.dist, 'km')                           distance,
       IF(SEEN.seenNum is null, 0, seenNum)              seenNum,
       REVIEW.reviewNum,
       RATING.rating,
       CASE
           WHEN (REVIEW.reviewNum = 0) THEN null
           WHEN (REVIEW.reviewNum <= 3) THEN 'gray'
           WHEN (REVIEW.reviewNum > 3) THEN 'orange' END ratingColor
FROM restaurant
         LEFT JOIN (select *
                    from rating) RATING ON RATING.restaurant_id = id
         LEFT JOIN (select restaurant_id, num seenNum
                    from seen) SEEN ON SEEN.restaurant_id = id
         LEFT JOIN (select restaurant_id,
                           IF(state = 'Y', 'YES', 'NO') star
                    from future
                    where user_id = ?) FUTURE ON FUTURE.restaurant_id = id
         LEFT JOIN (select restaurant_id, COUNT(*) reviewNum
                    from review
                    group by restaurant_id) REVIEW ON REVIEW.restaurant_id = id
         LEFT JOIN (select *
                    from (select rv.restaurant_id, REIMG.image_url, rv.created_at
                          from review rv
                                   LEFT JOIN (select *
                                              from review_image
                                              group by review_id) REIMG ON REIMG.review_id = rv.id
                          where image_url is not null
                          order by restaurant_id, created_at asc
                          LIMIT 18446744073709551615) as a
                    group by a.restaurant_id) IMG ON IMG.restaurant_id = id
         JOIN(select a.id a_id, a.name a_name from area a) AREA ON AREA.a_id = restaurant.area_id
         JOIN (select restaurant.id                                                                 rId,
                      ROUND(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng)
                          - radians($lng)) + sin(radians($lat)) * sin(radians(lat))), 2) dist
               from restaurant) DIST ON DIST.rId = restaurant.id
         LEFT JOIN (select restaurant_id, price, parking, kind from information) INFO ON INFO.restaurant_id = id";


/*    where area_id = ?
  and DIST.dist < 3
order by rating desc;";*/


    $filter = " where " . $area ;
    if (isset($kind)) {
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
    }
    $filter = $filter . " " . $order . ";";

    $query = $query . $filter;

    // echo $query;

    $st = $pdo->prepare($query);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    // title 앞에 번호 붙이기
    foreach ($res as $key => $value) {

        $res[$key]['title'] = ($key + 1) . ". " . $res[$key]['title'];

    }

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
