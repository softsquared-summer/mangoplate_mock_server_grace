<?php


function isExistUserId($userId)
{

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM user u WHERE u.id= ?) AS exist;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$userId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]["exist"]);
}


function getfollower($myId, $otherId)
{
    $pdo = pdoSqlConnect();
    $query = "select id                                     userId,
       name,
       profile_url                            profileUrl,
       REVIEW.reviewNum,
       FOLLOWER.followerNum,
       IF(ME.userId is not null, 'YES', 'NO') myFollowing
from user
         LEFT JOIN (select user_id, COUNT(*) reviewNum
                    from review
                    group by user_id) REVIEW ON REVIEW.user_id = id
         LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                    from friend
                    group by friend_id) FOLLOWER ON FOLLOWER.friend_id = id
         LEFT JOIN (select id userId
                    from user
                             LEFT JOIN (select user_id, COUNT(*) reviewNum
                                        from review
                                        group by user_id) REVIEW ON REVIEW.user_id = id
                             LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                                        from friend
                                        group by friend_id) FOLLOWER_NUM ON FOLLOWER_NUM.friend_id = id
                             JOIN (select friend_id
                                   from friend
                                   where user_id = ?) FOLLOWER ON FOLLOWER.friend_id = id) ME ON ME.userId = id
JOIN(select id          userId
from user
         LEFT JOIN (select user_id, COUNT(*) reviewNum
                    from review
                    group by user_id) REVIEW ON REVIEW.user_id = id
         LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                    from friend
                    group by friend_id) FOLLOWER_NUM ON FOLLOWER_NUM.friend_id = id
JOIN (select user_id
from friend
where friend_id = ?) FOLLOWING ON FOLLOWING.user_id = id) WER ON WER.userId = id;";

    $st = $pdo->prepare($query);
    $st->execute([$myId, $otherId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}


function getfollowing($myId, $otherId)
{
    $pdo = pdoSqlConnect();
    $query = "select id                                     userId,
       name,
       profile_url                            profileUrl,
       REVIEW.reviewNum,
       FOLLOWER.followerNum,
       IF(ME.userId is not null, 'YES', 'NO') myFollowing
from user
         LEFT JOIN (select user_id, COUNT(*) reviewNum
                    from review
                    group by user_id) REVIEW ON REVIEW.user_id = id
         LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                    from friend
                    group by friend_id) FOLLOWER ON FOLLOWER.friend_id = id
         LEFT JOIN (select id userId
                    from user
                             LEFT JOIN (select user_id, COUNT(*) reviewNum
                                        from review
                                        group by user_id) REVIEW ON REVIEW.user_id = id
                             LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                                        from friend
                                        group by friend_id) FOLLOWER_NUM ON FOLLOWER_NUM.friend_id = id
                             JOIN (select friend_id
                                   from friend
                                   where user_id = ?) FOLLOWER ON FOLLOWER.friend_id = id) ME ON ME.userId = id
JOIN(select id          userId
from user
         LEFT JOIN (select user_id, COUNT(*) reviewNum
                    from review
                    group by user_id) REVIEW ON REVIEW.user_id = id
         LEFT JOIN (select friend_id, COUNT(user_id) followerNum
                    from friend
                    group by friend_id) FOLLOWER_NUM ON FOLLOWER_NUM.friend_id = id
         JOIN (select friend_id
               from friend
               where user_id = ?) FOLLOWER ON FOLLOWER.friend_id = id) WING ON WING.userId = id;";

    $st = $pdo->prepare($query);
    $st->execute([$myId, $otherId]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

