<?php

// 4. 식당
function getUserId($userEmail)
{
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
                                              from restaurant_image
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

function getRestaurants($lat, $lng, $userId, $area, $kind, $price, $radius, $order, $category, $parking, $keyword, $paging, $page, $size)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT area_id                                           areaId,
       AREA.a_name                                       area,
       id                                                restaurantId,
       lat,
       lng,
       IF(image_url is null, '', image_url)              img,
       IF(FUTURE.star is null, 'NO', star)               star,
       name                                              title,
       CONCAT(DIST.dist, 'km')                           distance,
       IF(SEEN.seenNum is null,
          0, seenNum)                                    seenNum,
       REVIEW.reviewNum,
       IF(RATING.rating is null, '', rating)             rating,
       CASE
           WHEN (REVIEW.reviewNum = 0) THEN ''
           WHEN (REVIEW.reviewNum <= 3) THEN 'gray'
           WHEN (REVIEW.reviewNum > 3) THEN 'orange' END ratingColor
FROM restaurant
         LEFT JOIN (select *
                    from rating) RATING ON RATING.restaurant_id = id
         LEFT JOIN (select restaurant_id, FORMAT(num, 0) seenNum
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
                                              from restaurant_image
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


    $search = " JOIN ((select restaurant_id RES_ID
                from menu
                WHERE name  LIKE '%" . $keyword . "%')
               UNION
               (select id RES_ID
                from restaurant
                where address  LIKE '%" . $keyword . "%'
                   or oldAddress  LIKE '%" . $keyword . "%'
                   or name  LIKE '%" . $keyword . "%')
               UNION
               (select restaurant_id RES_ID
                from keyword
                where keyword  LIKE '%" . $keyword . "%'
               )) KEYWORD ON KEYWORD.RES_ID = id";


    if (isset($keyword)) {
        $query = $query . $search;
    }


    /*    where area_id = ?
      and DIST.dist < 3
    order by rating desc
    limit ;";*/


    $filter = " where ";

    if (isset($area)) {
        $filter = $filter . $area . " and ";
    }
    if (isset($kind)) {
        $filter = $filter . $kind . " and ";
    }
    if (isset($price)) {
        $filter = $filter . $price . " and ";
    }
    if (isset($radius)) {
        $filter = $filter . $radius . " and ";
    }
    if (isset($category)) {
        $filter = $filter . $category . " and ";
    }
    if (isset($parking)) {
        $filter = $filter . $parking . " and ";
    }

    $filter = substr($filter, 0, -4);
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

    $filter = $filter . " " . $order;

    $query = $query . $filter . $paging . ";";

//      echo $query;

    $st = $pdo->prepare($query);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    // title 앞에 번호 붙이기
    foreach ($res as $key => $value) {

        $res[$key]['title'] = ($key + 1) + $size * ($page - 1) . ". " . $res[$key]['title'];

    }

    $st = null;
    $pdo = null;

    return $res;
}

function isExistRestaurant($restaurantId)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM restaurant r WHERE r.id= ?) AS exist;";


    $st = $pdo->prepare($query);
    $st->execute([$restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]["exist"]);
}

function getRestaurant($userId, $restaurantId)
{
    $pdo = pdoSqlConnect();

    try {

        $pdo->beginTransaction();


        $updateQuery = "UPDATE seen
SET num = num + 1
WHERE restaurant_id = ?;";

        $st = $pdo->prepare($updateQuery);
        $st->execute([$restaurantId]);


        $query = "select name,
lat, lng,
       SEEN.seenNum,
       REVIEW.reviewNum,
       IF(STAR.starNum is null, 0, STAR.starNum)         starNum,
       IF(RATING.rating is null, '', rating) rating,
       CASE
           WHEN (REVIEW.reviewNum = 0) THEN  ''
           WHEN (REVIEW.reviewNum <= 3) THEN 'gray'
           WHEN (REVIEW.reviewNum > 3) THEN 'orange' END ratingColor,
       IF(FUTURE.star is null, 'NO', star)               userStar,
       address,
       oldAddress,
       IF(phone is null, '', phone) phone,
       USER.id userId,
       USER.userName,
       IF(USER.profile_url is null, '', USER.profile_url) userProfileUrl,
       INFO.infoUpdate, infoTime, infoHoliday, infoDescription, infoPrice, infoKind, infoParking, infoSite
from restaurant
         LEFT JOIN (select restaurant_id,
                           FORMAT(num, 0) seenNum
                    from seen) SEEN ON SEEN.restaurant_id = id
         LEFT JOIN (select restaurant_id, FORMAT(COUNT(*), 0) reviewNum
                    from review
                    group by restaurant_id) REVIEW ON REVIEW.restaurant_id = id
         LEFT JOIN (select restaurant_id, COUNT(*) starNum
                    from future
                    group by restaurant_id) STAR ON STAR.restaurant_id = id
         LEFT JOIN (select *
                    from rating) RATING ON RATING.restaurant_id = id
         LEFT JOIN (select restaurant_id,
                           IF(state = 'Y', 'YES', 'NO') star
                    from future
                    where user_id = ?) FUTURE ON FUTURE.restaurant_id = id
         LEFT JOIN (select id, name userName, profile_url
                    from user) USER ON USER.id = restaurant.user_id
LEFT JOIN (select restaurant_id rId,
                  CONCAT('마지막 업데이트: ', date_format(updated_at, '%Y-%m-%d')) infoUpdate,
       time                                                      infoTime,
       holiday                                                   infoHoliday,
       info infoDescription,
       CASE
           WHEN (price = '0') THEN '만원 이하'
           WHEN (price = '1') THEN '만원 - 2만원'
           WHEN (price = '2') THEN '2만원 - 3만원'
           WHEN (price = '3') THEN '3만원 이상' END                  infoPrice,
       kind                                                      infoKind,
       CASE
           WHEN (parking = '유료') THEN '유료주차 가능'
           WHEN (parking = '무료') THEN '무료주차 가능'
           WHEN (parking is null) THEN '주차공간없음' 
           WHEN (parking ='') THEN '주차공간없음'
           END              infoParking,
       site infoSite
from information) INFO ON INFO.rId = restaurant.id
where restaurant.id = ?;";

        $st = $pdo->prepare($query);
        $st->execute([$userId, $restaurantId]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();


        $pdo->commit();

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
            return null;
        }
        throw $e;
    }


    $st = null;
    $pdo = null;

    return $res[0];
}

function getRestaurantImages($restaurantId)
{
    $pdo = pdoSqlConnect();
    $query = "select id imageId,
       image_url imageUrl
from restaurant_image
         RIGHT JOIN (select id reviewId, created_at
                     from review
                     where restaurant_id = ?) REVIEW ON REVIEW.reviewId = review_id
where image_url is not null
order by created_at desc
limit 5;";

    $st = $pdo->prepare($query);
    $st->execute([$restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function getRestaurantKeywords($restaurantId)
{
    $pdo = pdoSqlConnect();
    $query = "select CONCAT('#', keyword) keyword
from keyword
where restaurant_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function getRestaurantMenu($restaurantId)
{
    $pdo = pdoSqlConnect();
    $query = "select name, FORMAT(price, 0) price
from menu
where restaurant_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

//    if (empty($res)) {
//        return null;
//    }
//    print_r($res);
    // print($res);
    return $res;
}

function getMenuUpdate($restaurantId)
{
    $pdo = pdoSqlConnect();
    $query = "select CONCAT('마지막 업데이트: ',date_format(updated_at, '%Y-%m-%d')) menuUpdate
from menu
where restaurant_id = ?
order by updated_at desc
limit 1;";

    $st = $pdo->prepare($query);
    $st->execute([$restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    // print_r($res);
    return $res[0];
}