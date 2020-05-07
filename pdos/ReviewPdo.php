<?php

function getReviews($restaurantId, $typeQuery)
{
    $pdo = pdoSqlConnect();
    $query = "select id reviewId,
       USER.userId,
       name,
       profileUrl,
       reviewNum,
       followerNum,
       CASE
           WHEN rating = 5 THEN '맛있다!'
           WHEN rating = 3 THEN '괜찮다'
           WHEN rating = 1 THEN '별로' END review,
         CASE WHEN
           length(content) > 100 THEN CONCAT(left(content, 100), '…')
            WHEN length(content) <= 100 THEN content END content,
                   date_format(created_at, '%Y-%m-%d') createdAt

from review
         LEFT JOIN (select id                                                         userId,
                           name,
                           IF(profile_url is null, '', profile_url)                   profileUrl,
                           IF(REVIEW.reviewNum is null, 0, REVIEW.reviewNum)         reviewNum,
                           IF(FOLLOWER.followerNum is null, 0, FOLLOWER.followerNum) followerNum
                    from user
                             LEFT JOIN (select user_id, COUNT(*) reviewNum
                                        from review
                                        group by user_id) REVIEW ON REVIEW.user_id = id
                             LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                                        from friend
                                        group by friend_id) FOLLOWER ON FOLLOWER.friend_id = id) USER
                   ON USER.userId = user_id
where restaurant_id = ? and isDeleted ='N'";

    $reviewArray = Array();

    $query = $query . $typeQuery;
    $st = $pdo->prepare($query);

    $st->execute([$restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    while ($row = $st->fetch()) {
        $reviewId = $row['reviewId'];
//        print_r($row);
        $row['images'] = getReviewImages($reviewId);

        array_push($reviewArray, $row);
    }


    $st = null;
    $pdo = null;

    return $reviewArray;
}

function getReviewImages($reviewId)
{
    $pdo = pdoSqlConnect();
    $query = "select id imageId,
       image_url imageUrl
from restaurant_image
         RIGHT JOIN (select id reviewId, created_at
                     from review
                     where restaurant_id = ? and isDeleted ='N') REVIEW ON REVIEW.reviewId = review_id
where image_url is not null
order by created_at desc
limit 5;";

    $st = $pdo->prepare($query);
    $st->execute([$reviewId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

// postReview를 위한 함수
function getRating($restaurantId){

    $pdo = pdoSqlConnect();
    $query = "select rating from rating where restaurant_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

//    print_r($res[0]);
    return $res[0]['rating'];
}
function getReviewNum($restaurantId){

    $pdo = pdoSqlConnect();
    $query = "select COUNT(*) num from review where restaurant_id =? and isDeleted = 'N' group by restaurant_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$restaurantId, $restaurantId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

//    print_r($res[0]);
    return $res[0]['num'];
}

function postReview($userId, $restaurantId, $review, $content, $imageList)
{

    $pdo = pdoSqlConnect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $reviewQuery = "INSERT INTO review (user_id, restaurant_id, content, rating, created_at) VALUES (?, ?, ?, ?, NOW());";
    $imageQuery = "INSERT INTO restaurant_image (review_id, image_url) VALUES (?, ?);";

    $insertQuery = "INSERT INTO rating (rating, restaurant_id) VALUES (?, ?);";
    $updateQuery = "UPDATE rating SET rating = ? WHERE restaurant_id = ?;";

    $rating = getRating($restaurantId);
    if(empty($rating)){
        $rating = 0;
        $ratingQuery = $insertQuery;
    }else{
        $ratingQuery = $updateQuery;
    }

    $num = getReviewNum($restaurantId);
    if(empty($num)){
        $num = 0;
    }

//    echo $rating;
//    echo $num;
    $finalRating = round((($rating * $num + $review) / ($num + 1)), 1);

    try {
        $reviewSt = $pdo->prepare($reviewQuery);
        $imageSt = $pdo->prepare($imageQuery);

        // rating 업데이트
        $ratingSt = $pdo->prepare($ratingQuery);

        $pdo->beginTransaction();

        // 1. review insert
        $reviewSt->execute([$userId, $restaurantId, $content, $review]);
        $reviewId = $pdo->lastInsertId();

        // 2. image insert
        foreach ($imageList as $key => $value) {
            $imageSt->execute([$reviewId, $value]);
        }

        // 3. update rating / 새로 계산된 finalRating를 집어 넣기
        $ratingSt->execute([$finalRating, $restaurantId]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        return $e->getMessage();
    }
    $st = null;
    $pdo = null;
}


function isExistReview($reviewId){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM review rv WHERE rv.id =?) AS exist;";


    $st = $pdo->prepare($query);
    $st->execute([$reviewId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);
}

function isMatchedReview($userId, $reviewId){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM review rv WHERE rv.id =? and rv.user_id = ?) AS exist;";


    $st = $pdo->prepare($query);
    $st->execute([$reviewId, $userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);
}
function isDeleted($reviewId){
    $pdo = pdoSqlConnect();
    $query = "select isDeleted from review where id = ?";


    $st = $pdo->prepare($query);
    $st->execute([$reviewId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["isDeleted"]);
}
function deleteReview($reviewId){

    $pdo = pdoSqlConnect();
    $query = "UPDATE review
SET isDeleted = 'Y'
WHERE id = ?;";

    
    try{
        $st = $pdo->prepare($query);
        $st->execute([$reviewId]);
    }catch (PDOException $e){
        return 'false';
    }


    $st = null;
    $pdo = null;
    
}

function getAllReviews(){

}