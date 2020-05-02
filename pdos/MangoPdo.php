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

function getUserId()
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
    $query = "select e.id eventId, e.image_url ImageUrl
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

       ROUND(6371 * acos(cos(radians(?)) * cos(radians(a.lat)) * cos(radians(a.lng)
           - radians(?)) + sin(radians(?)) * sin(radians(a.lat))), 2)
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
