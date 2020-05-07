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
where restaurant_id = ?";

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
                     where restaurant_id = ?) REVIEW ON REVIEW.reviewId = review_id
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

function postReview($userId, $restaurantId, $review, $content, $imageList)
{

    $pdo = pdoSqlConnect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $reviewQuery = "INSERT INTO review (user_id, restaurant_id, content, rating, created_at) VALUES (?, ?, ?, ?, NOW());";
    $imageQuery = "INSERT INTO restaurant_image (review_id, image_url) VALUES (?, ?);";

    try {
        $reviewSt = $pdo->prepare($reviewQuery);
        $imageSt = $pdo->prepare($imageQuery);

        $pdo->beginTransaction();

        $reviewSt->execute([$userId, $restaurantId, $content, $review]);
        $reviewId = $pdo->lastInsertId();

        foreach ($imageList as $key => $value) {
            $imageSt->execute([$reviewId, $value]);
        }

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