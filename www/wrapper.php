<?php

Header('Access-Control-Allow-Origin: *');
require_once( "../config.php" );
/*
$globalapp = array();

//Database informations
$globalapp["url"] = 'http://localhost/mobileOqra';
$globalapp['db_server'] = 'localhost';
$globalapp['db_user'] = 'root';
$globalapp['db_password'] = '';
$globalapp['db_name'] = 'oqra';
*/

$query = array();
$query["select_location"] = "SELECT * FROM location WHERE client_id = ".$globalapp['client_id']." ORDER BY id ASC";
$query["select_client_with_configuration"] ="SELECT * FROM client c join client_configuration cc on c.id=cc.client_id where c.id= ".$globalapp['client_id'];

error_reporting(0);
ini_set('display_error','Off');

$sid = 'AC757ca2e4dd9cba61ac6b138785ea6405';
$token = 'e905c5307d4beffb60ff3409330e4a48';

/*
// database
require_once( "database.class.php" );
$db = new Database( $globalapp['db_server'], $globalapp['db_user'], $globalapp['db_password'], $globalapp['db_name'], $globalapp['table_prefix'] );
*/

if( isset($_POST["action"]) ) {

}
else if ( isset($_GET['route']) ){
    global $globalapp;
    switch ($_GET['route']){
        case 'err': err($globalapp,$query); break;
        case 'closest': closest($globalapp,$query); break;
        case 'fallbackgps': fallbackgps($globalapp,$query); break;
        case 'dissatisfied': dissatisfied($globalapp,$query); break;
        case 'rateit': rateit($globalapp,$query); break;
        case 'logshare': logshare($globalapp,$query); break;
        case 'sendsocialsms': sendsocialsms($globalapp,$query); break;
        case 'sendsms': sendsmsFunc($globalapp,$query); break;
        case 'submitreview': submitreview($globalapp,$query); break;
        case 'loadurl': loadurl($globalapp,$query); break;
        case 'loaddata': loaddata($globalapp,$query); break;
        case 'finish': finish($globalapp,$query); break;
        case 'search': search($globalapp,$query); break;
        case 'uploadvoice': uploadvoice($globalapp,$query); break;
    }
}

function err(){
    $data = json_decode(file_get_contents("php://input"),true);
    $err = $data['data'];
    $myfile = fopen("testfile.txt", "a");
    fwrite($myfile,"DEBUG: $err\r\n");
    fclose($myfile);
}

function closest($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $origLon = $data['data']['lon'];
    $origLat = $data['data']['lat'];

    $dist = 50000; // miles
    $tableName = 'location';
    $query = "SELECT id, location_name, address, city, zip_code, state, latitude, longitude, 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - latitude)*pi()/180/2),2)+COS($origLat*pi()/180 )*COS(latitude*pi()/180)*POWER(SIN(($origLon-longitude)*pi()/180/2),2))) as distance FROM $tableName WHERE client_id = ".$globalapp['client_id']." AND longitude between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat))*69) and latitude between ($origLat-($dist/69)) and ($origLat+($dist/69)) having distance < $dist ORDER BY distance limit 3";

    $q = func_query($query);

    if (!$q) {
        $res = func_query($query["select_location"]);
        echo json_encode(array('status'=>false,'msg'=>'No locations found within radius of ' . $dist . ' miles from your current location. Sorry, we can\'t continue.','results'=>$res));
    } else {
        $final = array();
        foreach ($q as $r) {
            $final[] = array('id'=>$r['id'],'name'=>$r['name'],'address'=>$r['address'],'city'=>$r['city'],'zip'=>$r['zip_code'],'state'=>$r['state']);
        }
        echo json_encode(array('status'=>true,'dist'=>$dist,'results'=>$final));
    }
    exit;
}

function fallbackgps($globalapp,$query){

    $q = func_query($query);

    if (!$q) {
        $res = func_query($query["select_location"]);
        echo json_encode(array('status'=>false,'msg'=>'No locations found. Sorry, we can\'t continue.','results'=>$res));
    } else {
        $final = array();
        foreach ($q as $r) {
            $final[] = array('id'=>$r['id'],'name'=>$r['name'],'address'=>$r['address'],'city'=>$r['city'],'zip'=>$r['zip_code'],'state'=>$r['state']);
        }
        echo json_encode(array('status'=>true,'dist'=>$this->dist,'results'=>$final));
    }
}

function newClient($globalapp,$query){ $data = json_decode(file_get_contents("php://input"),true);
    $firstname = $data['data']['Firstname'];
    $lastname = $data['data']['Lastname'];
    $email = $data['data']['Email'];
    $cell = $data['data']['Cell'];
    /** Already exists message **/

    $result = func_query_first($query["select_client_with_configuration"]);
    $result = ($result['exists_message'] == '') ? $result = 'This number already exits. Do you want to make a search?' : $result['exists_message'];

    $check = func_query_first("SELECT 1 FROM user WHERE phone = '$cell' AND client_id = ". $globalapp['client_id']);
    if ($check) {
        echo json_encode(array('status'=>false,'msg'=>$result));
        exit;
    }
    $sql_query = "INSERT INTO user (first_name,last_name,phone,email,is_delete,client_id) VALUES ('$firstname','$lastname','$cell','$email','0',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection, $sql_query);

    $id = mysqli_insert_id($this->db->connection);

    echo json_encode(array('status'=>true,'id'=>$id));
}

function dissatisfied($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $title = $data['data']['title'];
    $desc = $data['data']['desc'];
    $location = $data['data']['location'];
    $date = date('Y-m-d H:i:s');
    $session_id = md5(time());
    $rating_id = $data['data']['ratingid'];
    echo json_encode(array('status'=>true));
}

function rateit($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $date = date('Y-m-d H:i:s');
    $session_id = md5(time());
    $rating_id = $data['data']['ratingid'];

    $sql_query = "INSERT INTO rating (user_id,rating,rating_no,session_id,entry_date,is_delete,client_id) VALUES ('$userid','$rate','$rate','$session_id','$date','0',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection,$sql_query);
    $rating_id = mysqli_insert_id($this->db->connection);

    echo json_encode(array('status'=>true,'id'=>$rating_id,'session'=>$session_id));
}

function logshare($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $type = $data['data']['type'];
    $location = $data['data']['location'];
    $date = date('Y-m-d H:i:s');

    $sql_query = "INSERT INTO socialshare (user_id,social_type,rating,entry_date,location_id,client_id) VALUES ('$userid','$type','$rate','$date','$location',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection,$sql_query);

    echo json_encode(array('status'=>true));
}

function sendsocialsms($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $location = $data['data']['location'];
    $network = $data['data']['network'];
    $date = date('Y-m-d H:i:s');
    $sql_query_insert_social_share = "INSERT INTO socialshare (user_id,social_type,rating,entry_date,location,client_id) VALUES ('$userid','$network','$rate','$date','$location',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection,$sql_query_insert_social_share);

    /** Get client phone number **/
    $send = false;
    $cnum = func_query_first("SELECT phone FROM user WHERE client_id = ".$globalapp['client_id']." AND id = $userid");
    if ($cnum) {
        /** Get review **/
        $sql_select_review = "SELECT review,rating_id FROM review WHERE client_id = ".$globalapp['client_id']." AND location_id = $location AND user_id = $userid ORDER BY id DESC LIMIT 1";
        $review = func_query_first($sql_select_review);
        if ($review) {
            debugf("Prepare SMS for " . $cnum['phone']. " Type of review: " . $review['review']);
            if ($review['review'] == 'Upload') {
                $sql_select_file_review = "SELECT file FROM review WHERE client_id = ".$globalapp['client_id']." AND user_id = $userid AND rating_id = " . $review['rating_id'] . " ORDER BY id LIMIT 1";
                $c = func_query_first($sql_select_file_review);
                if ($c) {
                    if (strpos($c['file'],'MOV') !== false) {
                        $textReview = 'http://localhost/mobileOqra/www/uploads/' . $c['file'];
                    } else if (strpos($c['file'],'mp4') !== false) {
                        $tm = basename($c['file']);
                        $textReview = 'http://localhost/mobileOqra/www/uploads/' . $tm;
                    } else {
                        $textReview = 'http://localhost/mobileOqra/www/audioDownload.php?name=' . basename($c['file']) . '.mp3';
                    }
                    $send = true;
                }
            } else {
                $sql_query_select_review = "SELECT review FROM review WHERE user_id = $userid AND client_id = ".$globalapp['client_id']." AND location_id = $location ORDER BY id DESC LIMIT 1";
                $textReview = func_query_first($sql_query_select_review);
                if ($textReview) {
                    $textReview = $textReview['review'];
                    $send = true;
                }
            }
        }
    }

    /** Gplus link **/
    $sql_query_link = "SELECT link FROM sr_social WHERE client_id = ".$globalapp['client_id']." AND location_id = $location AND status = 1 AND name = '$network' LIMIT 1";
    $glink = func_query_first($sql_query_link);
    if ($glink && $send) {
        $gpluslink = $glink['link'];
        debugf("Send sms is true for " . $cnum['phone']);
        sendSms($cnum['phone'],"Thanks for your review.\n\nPlease COPY your review:\n$textReview\n\nCLICK & PASTE below:\n$gpluslink",true);
    }

    echo json_encode(array('status'=>true));
}

function sendsmsFunc($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $location = $data['data']['location'];
    $date = date('Y-m-d H:i:s');
    $sql_query_insert_socialshare = "INSERT INTO socialshare (user_id,social_type,rating,entry_date,location_id,client_id) VALUES ('$userid','google','$rate','$date','$location',".$globalapp['client_id'].")";

    mysqli_query($this->db->connection,$sql_query_insert_socialshare);

    /** Get client phone number **/
    $send = false;
    $sql_query_select_phone = "SELECT phone FROM user WHERE client_id = " .$globalapp['client_id']. " AND id = $userid";
    $cnum = func_query_first($sql_query_select_phone);
    if ($cnum) {
        /** Get review **/
        $review = func_query_first("SELECT review,rating_id FROM review WHERE client_id = " .$globalapp['client_id']. " AND location_id = $location AND user_id = $userid ORDER BY id DESC LIMIT 1");
        if ($review) {
            debugf("Prepare SMS for " . $cnum['phone']. " Type of review: " . $review['review']);
            if ($review['review'] == 'Upload') {
                $sql_query_select_file = "SELECT file FROM review WHERE client_id = " .$globalapp['client_id']. " AND user_id = $userid AND rating_id = " . $review['rating_id'] . " ORDER BY id LIMIT 1";
                $c = func_query_first($sql_query_select_file);
                if ($c) {
                    if (strpos($c['file'],'MOV') !== false) {
                        $textReview = 'http://localhost/mobileOqra/www/uploads/' . $c['file'];
                    } else if (strpos($c['file'],'mp4') !== false) {
                        $tm = basename($c['file']);
                        $textReview = 'http://localhost/mobileOqra/www/uploads/' . $tm;
                    } else {
                        $textReview = 'localhost/mobileOqra/www/audioDownload.php?name=' . basename($c['file']) . '.mp3';
                    }
                    $send = true;
                }
            } else {
                $sql_query_select_review = "SELECT review FROM review WHERE user_id = $userid AND client_id = ".$globalapp['client_id']." AND location_id = $location ORDER BY id DESC LIMIT 1";
                $textReview = func_query_first($sql_query_select_review);
                if ($textReview) {
                    $textReview = $textReview['review'];
                    $send = true;
                }
            }
        }
    }

    /** Gplus link **/
    $sql_query_select_link = "SELECT link FROM social WHERE client_id = ". $globalapp['client_id'] ." AND location_id = $location AND status = 1 AND name = 'google' LIMIT 1";
    $glink = func_query_first($sql_query_select_link);
    if ($glink && $send) {
        $gpluslink = $glink['link'];
        debugf("Send sms is true for " . $cnum['phone']);
        sendSms($cnum['phone'],"Thanks for your review.\n\nPlease COPY your review:\n$textReview\n\nCLICK & PASTE below:\n$gpluslink",true);
    }

    echo json_encode(array('status'=>true));
}

function submitreview($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['your_rate'];
    $review = $data['data']['comment'];
    $location = $data['data']['location'];
    $session_id = md5(time() + rand(1,1111111));
    $date = date('Y-m-d H:i:s');
    $sql_query_select_logo_client = "SELECT logo FROM client WHERE client_id = ". $globalapp['client_id'];

    if ($userid == '' || $rate == '' || $review == '') {
        echo json_encode(array('status'=>false));
        exit;
    }

    /** Debug **
    if ($userid == 2) {
    echo json_encode(array('status'=>true));
    exit;
    }*/

    if ($review == 'Upload') {
        echo json_encode(array('status'=>false));
        exit;
    }

    /** Debug **
    $results = mysqli_query($this->db->connection,"SELECT senderEmail,emails3,email_4_5,3_under_mail,email_img,email_img1,sms_3_under,sms_45 FROM sr_locations WHERE id = $location AND client_id = $globalapp['client_id']");
    $r = mysqli_fetch_array($results,MYSQL_ASSOC);
    sendSms($r['sms_132'],'Test');
    sendSms($r['sms_45'],'Test');
    $cnum = func_query_first("SELECT phone FROM user WHERE client_id = $globalapp['client_id'] AND id = $userid");
    sendSms($cnum['phone'],'Test',true);
    echo json_encode(array('status'=>true));
    exit;*/


    /** Insert data **/
    $sql_query_insert_rating = "INSERT INTO rating (user_id,rating,rating_no,session_id,entry_date,is_delete,location,client_id) VALUES ('$userid','$rate','$rate','$session_id','$date','0','$location',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection,$sql_query_insert_rating);
    $rating_id = mysqli_insert_id($this->db->connection);

    /** Insert rating **/
    $status = ($rate < 4) ? 0 : 1;

    $review = mysqli_real_escape_string($this->db->connection,$review);

    $sql_query_insert_review = "INSERT INTO review (user_id,review,rating_id,rating_no,status,location,client_id) VALUES ('$userid','$review','$rating_id','$rate','$status','$location',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection,$sql_query_insert_review);

    debugf("User id: $userid submitted text review at $date");

    /** Send email **/
    $sql_query_select_review = "SELECT senderEmail,emails3,email_4_5,3_under_mail,email_img,email_img1,sms_3_under,sms_4_5 FROM sr_locations WHERE id = $location AND client_id = ".$globalapp['client_id'];
    $results = mysqli_query($this->db->connection,$sql_query_select_review);
    $r = mysqli_fetch_array($results,MYSQL_ASSOC);
    $email = $r['email'];

    /** Get firstname of customer **/
    $sql_query_select_user = "SELECT first_name,last_name,email,phone FROM user WHERE id = $userid AND client_id = ". $globalapp['client_id'];
    $fname = mysqli_query($this->db->connection,$sql_query_select_user);
    $f = mysqli_fetch_array($fname,MYSQL_ASSOC);

    if ($rate <= 3) {
        debugf('Sending written review for ' . $rate . ' stars - Location: ' . $location);
        $text = str_replace('%s',$f['first_name'],$r['3_under_mail']);
        $text = str_replace('%r',$rate,$text);
        if ($f['email'] != '') {
            $text = str_replace('%e',$f['email'],$text);
        } else {
            $text = str_replace('%e','No email address entered',$text);
        }
        $text = str_replace('%p',$f['phone'],$text);
        $text = str_replace('%l',$f['last_name'],$text);
        $text = str_replace('%t',nl2br($review),$text);
        $text = nl2br($text);
        $file = file_get_contents('templates/3under.php');
        $file = str_replace('%text',$text,$file);

        $clogo = func_query_first($sql_query_select_logo_client);
        if ($clogo) {
            $cl = 'http://localhost/mobileOqra/www/logos/' . $clogo['logo'];
            $file = str_replace('%sitelogo',$cl,$file);
        }

        /** Text messaging **/
        $smsText = str_replace('%s',$f['first_name'],$r['3_under_mail']);
        $smsText = str_replace('%r',$rate,$smsText);
        if ($f['email'] != '') {
            $smsText = str_replace('%e',$f['email'],$smsText);
        }
        $smsText = str_replace('%p',$f['phone'],$smsText);
        $smsText = str_replace('%l',$f['last_name'],$smsText);
        $revCopy = str_replace('\n',' ',$review);
        $smsText = str_replace('%t',$revCopy,$smsText);

        sendSms($r['sms_3_under'],$smsText);

        /** Mobile text **/
        $mobile = false;
        $mobile = $r['email_3_under'];
        $mobile = str_replace('%s',$f['first_name'],$mobile);
        $mobile = str_replace('%r',$rate,$mobile);
        if ($f['email'] != '') {
            $mobile = str_replace('%e',$f['email'],$mobile);
        } else {
            $mobile = str_replace('%e','No email address entered',$mobile);
        }
        $mobile = str_replace('%p',$f['phone'],$mobile);
        $mobile = str_replace('%l',$f['last_name'],$mobile);
        $mobile = str_replace('%t',$review,$mobile);

        /** Check image **/
        if ($r['email_image_3_under'] != '') {
            $image = "<div class='image' style='font-size: 12px;mso-line-height-rule: at-least;font-style: normal;font-weight: 400;Margin-bottom: 0;Margin-top: 0;font-family: 'Open Sans',sans-serif;color: #60666d;' align='center'>
              <img class='gnd-corner-image gnd-corner-image-center gnd-corner-image-top' style='border: 0;-ms-interpolation-mode: bicubic;display: block;max-width: 900px;' src='".$r['email_img1']."' alt='' width='600' height='156' />
            </div>";
            $file = str_replace('%image',$image,$file);
        } else {
            $file = str_replace('%image','',$file);
        }

        /** Send emails for 3 and under **/
        $sql_query_select_email = "SELECT email_3_under FROM location l join location_configuration lc on l.id = lc.location_id WHERE l.id = $location AND client_id = ".$globalapp['client_id'];
        $three_and_under = func_query_first($sql_query_select_email);
        $mails = explode(',',$three_and_under['email_3_under']);
        foreach ($mails as $mail) {

            if (strpos($mail,'mms.att') !== false || strpos($mail,'vtext.com') !== false || strpos($mail,'txt.att') !== false || strpos($mail,'tmomail.net') !== false) {
                debugf("Sent textual message to mms / sms $mail");
                sendMobile($mail,$mobile,'Reviews');
            } else {
                debugf("Sent review email to $mail for stars $rate - Location Id: $location Sent from email: {$r['senderEmail']}");
                send_email($mail,'New Review for company',$file,$r['senderEmail'],'OQRA EO');

            }
        }
    } else {
        debugf('Sending written review for ' . $rate . ' stars - Location: ' . $location);
        $text_for_admin = $f['first_name'] . ' has reviewed your company with ' . $rate . ' stars. <br />Email: ' . $f['email'] . '<br />Phone: ' . $f['phone'] . '<br />Review:<br />'.nl2br($review);
        $file1 = file_get_contents('templates/3under.php');
        $file1 = str_replace('%text',$text_for_admin,$file1);

        $clogo = func_query_first($sql_query_select_logo_client);
        if ($clogo) {
            $cl = 'http://localhost/mobileOqra/www/logos/' . $clogo['logo'];
            $file1 = str_replace('%sitelogo',$cl,$file1);
        }

        $text = str_replace('%s',$f['first_name'],$r['email_4_5']);
        $text = str_replace('%r',$rate,$text);
        if ($f['email'] != '') {
            $text = str_replace('%e',$f['email'],$text);
        } else {
            $text = str_replace('%e','No email address entered',$text);
        }
        $text = str_replace('%p',$f['phone'],$text);
        $text = str_replace('%l',$f['last_name'],$text);
        $text = str_replace('%t',nl2br($review),$text);
        $text = nl2br($text);
        $file = file_get_contents('templates/template.php');
        $file = str_replace('%text',$text,$file);

        $clogo = func_query_first($sql_query_select_logo_client);
        if ($clogo) {
            $cl = 'http://localhost/mobileOqra/www/logos/' . $clogo['logo'];
            $file = str_replace('%sitelogo',$cl,$file);
        }

        /** Text messaging **/
        $sms_4_5 = "Hello,\n" . $f['first_name'] . " submitted new review.\n\nRate $rate stars\n\nPhone: " . $f['phone'] . "\n\nReview:\n\n$review";
        sendSms($r['sms_4_5'],$sms_4_5);

        /** Mobile text **/
        $mobile = false;
        $mobile = $r['email_4_5'];
        $mobile = str_replace('%s',$f['first_name'],$mobile);
        $mobile = str_replace('%r',$rate,$mobile);
        if ($f['email'] != '') {
            $mobile = str_replace('%e',$f['email'],$mobile);
        } else {
            $mobile = str_replace('%e','No email address entered',$mobile);
        }
        $mobile = str_replace('%p',$f['phone'],$mobile);
        $mobile = str_replace('%l',$f['lastname'],$mobile);
        $mobile = str_replace('%t',$review,$mobile);

        /** Check image **/
        if ($r['email_img'] != '') {
            $image = "<div class='image' style='font-size: 12px;mso-line-height-rule: at-least;font-style: normal;font-weight: 400;Margin-bottom: 0;Margin-top: 0;font-family: 'Open Sans',sans-serif;color: #60666d;' align='center'>
              <img class='gnd-corner-image gnd-corner-image-center gnd-corner-image-top' style='border: 0;-ms-interpolation-mode: bicubic;display: block;max-width: 900px;' src='".$r['email_img']."' alt='' width='600' height='156' />
            </div>";
            $file = str_replace('%image',$image,$file);
        } else {
            $file = str_replace('%image','',$file);
        }

        send_email($f['email'],'Thank you for your review',$file,$r['senderEmail'],$f['first_name']);
        debugf("Sent review (for customer/patient) email to ".$f['email']." for stars $rate - Location Id: $location");

        $sql_query_select = "SELECT email_4_5 FROM locations WHERE id = $location AND client_id = ". $globalapp['client_id'];
        $three_and_under = func_query_first($sql_query_select);
        $mails = explode(',',$three_and_under['email_4_5']);
        foreach ($mails as $mail) {
            if (strpos($mail,'mms.att') !== false || strpos($mail,'vtext.com') !== false || strpos($mail,'txt.att') !== false || strpos($mail,'tmomail.net') !== false) {
                debugf("Sent textual message to mms / sms $mail");
                sendMobile($mail,$mobile,'Reviews');
            } else {
                debugf("Sent review email to $mail for stars $rate - Location Id: $location");
                send_email($mail,'New Review for company',$file1,$r['senderEmail'],'OQRA EO');
            }
        }
    }

    echo json_encode(array('status'=>true));
}

function loadurl($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $network = $data['data']['type'];

    if (isset($_GET['lid']) && $_GET['lid'] != '') {
        $loc_id = (int)$_GET['lid'];
    } else {
        $q = func_query_first("SELECT id FROM sr_locations WHERE client_id = ". $globalapp['client_id'] ." ORDER BY id ASC LIMIT 1");
        $loc_id = $q['id'];
    }

    $q = func_query_first("SELECT link,description,logo FROM sr_social WHERE name = '$network' AND location_id = $loc_id AND client_id = ". $globalapp['client_id']." LIMIT 1");
    if ($q) {
        if ($network == 'fb') {
            $link = 'http://www.facebook.com/sharer/sharer.php?s=100&&p[url]=' . urlencode($q['link']);
            echo json_encode(array('status'=>true,'link'=>$link));
        } else if ($network == 'yelp') {
            echo json_encode(array('status'=>true,'link'=>$q['link']));
        } else if ($network == 'pinterest') {
            if ($q['logo'] == '') {
                echo json_encode(array('status'=>false));
                exit;
            }
            $link = 'http://www.pinterest.com/pin/create/button/?url=' . urlencode($q['link']) . '&media=' . urlencode($q['logo']) . '&description=' . urlencode($q['description']);
            echo json_encode(array('status'=>true,'link'=>$link));
        } else if ($network == 'twitter') {
            $link = 'https://www.twitter.com/share?text=' . urlencode($q['description']) . '&url=' . urlencode($q['link']);
            echo json_encode(array('status'=>true,'link'=>$link));
        }
        exit;
    }

    echo json_encode(array('status'=>false));
}

function loaddata($globalapp,$query){
    if (isset($_GET['lid']) && $_GET['lid'] != '') {
        $loc_id = (int)$_GET['lid'];
    } else {
        /** Get first location **/
        $sql_query_select_id_location = "SELECT id FROM location WHERE client_id = ". $globalapp['client_id'] ." ORDER BY id ASC LIMIT 1";
        $q = func_query_first($sql_query_select_id_location);
        $loc_id = $q['id'];
    }

    /** Get timeout **/
    $sql_query_select_timeout_client = "SELECT timeout FROM client WHERE id = ".$globalapp['client_id'];
    $timeOut = func_query_first($sql_query_select_timeout_client);
    $timeOut = ($timeOut['timeout'] == '') ? 0 : ($timeOut['timeout'] * 60);

    /** Get question **/
    $sql_query_select_question_client = "SELECT question FROM client WHERE id = ". $globalapp['client_id'];
    $question = func_query_first($sql_query_select_question_client);
    $question = ($question['question'] == '') ? 'How did you find your experience today?' : $question['question'];

    $sql_query_select_location = "SELECT * FROM sr_locations WHERE id = $loc_id AND client_id = " .$globalapp['client_id'];
    $results = mysqli_query($this->db->connection,$sql_query_select_location);
    $r = mysqli_fetch_array($results,MYSQL_ASSOC);

    $social = false;
    $google = false;
    $sql_query_select_social = "SELECT name,status FROM social WHERE active = 1 AND location_id = $loc_id AND client_id = ".$globalapp['client_id']." ORDER BY name ASC";
    $social_networks = mysqli_query($this->db->connection,$sql_query_select_social);
    if (mysqli_num_rows($social_networks) > 0) {
        while ($row = $social_networks->fetch_assoc()) {
            if ($row['name'] == 'google' && $row['active'] == 1) $google = true;
            $social[] = $row['name'];
        }
    }
    $sql_query_select_count_location = "SELECT COUNT(*) AS total, id FROM location WHERE client_id = ". $globalapp['client_id'];
    $q = func_query_first($sql_query_select_count_location);
    if ($q['total'] == 1) {
        $locations = 1;
        $location_id = $q['id'];
    } else {
        $locations = $q['total'];
        $location_id = false;
    }

    echo json_encode(array('status'=>true,'google'=>$google,'timeout'=>$timeOut,'question'=>$question,'data'=>$r,'social'=>$social,'url'=>$globalapp['url'],'total'=>$locations,'location_id'=>$location_id));
}

function finish($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $file = $data['data']['file'];
    $rate = $data['data']['your_rate'];
    $location = $data['data']['location'];
    $session_id = md5(time() + rand(1,1111111));
    $date = date('Y-m-d H:i:s');
    $date1 = time();
    $sql_query_select_logo_client = "SELECT logo FROM client WHERE client_id = " .$globalapp['client_id'];

    $back = $file;
    $fileinfo = pathinfo($back);
    $ext = $fileinfo['extension'];

    $temp = explode('/',$file);
    if ($temp[1] == 'private') {
        $file = end($temp);
        if ($file == 'capturedvideo.MOV') {
            $file = rand(1,10000).'_'.time().'.MOV';
            @rename('../cadmin/uploads/capturedvideo.MOV','../cadmin/uploads/'.$file);
        }
    }

    /** Get extension of the file **/

    if ($ext == 'MOV') {
        $link = 'http://localhost/mobileOqra/www/uploads/' . basename($file);
    } elseif ($ext == 'm4a') {
        $link = 'http://1qreputation.com/audioDownload.php?name=' . basename($back) . '.mp3';
    } elseif ($ext == '3gp') {
        $link = 'http://localhost/mobileOqra/www/done/' . basename($back) . '.mp4';
    } elseif ($ext == 'mp4') {
        $link = 'http://localhost/mobileOqra/www/uploads/' . basename($back);
    }

    /** Insert data **/
    $sql_query_insert_rating = "INSERT INTO rating (user_id,rating,rating_no,session_id,entry_date,is_delete,location,client_id) VALUES ('$userid','$rate','$rate','$session_id','$date','0','$location',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection,$sql_query_insert_rating);
    $rating_id = mysqli_insert_id($this->db->connection);

    /** Insert rating **/
    if ($rate < 4) {
        $status = 0;
    } else {
        $status = 1;
    }
    $sql_query_insert_review = "INSERT INTO review (user_id,review,rating_id,rating_no,status,location,client_id) VALUES ('$userid','Upload','$rating_id','$rate','$status','$location',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection,$sql_query_insert_review);

    $sql_query_insert_reviews = "INSERT INTO sr_reviews (user_id,file,date,rating_id,client_id) VALUES ('$userid','$file','$date1','$rating_id',".$globalapp['client_id'].")";
    mysqli_query($this->db->connection,$sql_query_insert_reviews);

    /** Send email **/
    $results = mysqli_query($this->db->connection,"SELECT senderEmail,emails3,email_4_5,email_3_under,email_img,email_img1,sms_4_5 FROM sr_locations WHERE id = $location AND client_id = ".$globalapp['client_id']);
    $r = mysqli_fetch_array($results,MYSQL_ASSOC);
    $email = $r['email'];

    /** Get first_name of customer **/
    $sql_query_select_user = "SELECT first_name,lastname,email,phone FROM user WHERE id = $userid AND client_id = ". $globalapp['client_id'];
    $fname = mysqli_query($this->db->connection,$sql_query_select_user);
    $f = mysqli_fetch_array($fname,MYSQL_ASSOC);

    if ($rate <= 3) {
        $text = str_replace('%s',$f['first_name'],$r['email_3_under']);
        $text = str_replace('%r',$rate,$text);
        if ($f['email'] != '') {
            $text = str_replace('%e',$f['email'],$text);
        } else {
            $text = str_replace('%e','No email address entered',$text);
        }
        $text = str_replace('%p',$f['phone'],$text);
        $text = str_replace('%l',$f['lastname'],$text);
        $text = str_replace('%t',$link,$text);
        $text = nl2br($text);
        $file = file_get_contents('templates/3under.php');
        $file = str_replace('%text',$text,$file);

        $sql_query_select_logo_client = "SELECT logo FROM client WHERE client_id = ". $globalapp['client_id'];
        $clogo = func_query_first();
        if ($clogo) {
            $cl = 'http://localhost/mobileOqra/www/logos/' . $clogo['logo'];
            $file = str_replace('%sitelogo',$cl,$file);
        }

        /** Text messaging **/
        $smsText = str_replace('%s',$f['first_name'],$r['email_3_under']);
        $smsText = str_replace('%r',$rate,$smsText);
        if ($f['email'] != '') {
            $smsText = str_replace('%e',$f['email'],$smsText);
        }
        $smsText = str_replace('%p',$f['phone'],$smsText);
        $smsText = str_replace('%l',$f['lastname'],$smsText);
        $smsText = str_replace('%t',$link,$smsText);

        sendSms($r['sms_3_under'],$smsText);

        /** Check image **/
        if ($r['email_img1'] != '') {
            $image = "<div class='image' style='font-size: 12px;mso-line-height-rule: at-least;font-style: normal;font-weight: 400;Margin-bottom: 0;Margin-top: 0;font-family: 'Open Sans',sans-serif;color: #60666d;' align='center'>
              <img class='gnd-corner-image gnd-corner-image-center gnd-corner-image-top' style='border: 0;-ms-interpolation-mode: bicubic;display: block;max-width: 900px;' src='".$r['email_img1']."' alt='' width='600' height='156' />
            </div>";
            $file = str_replace('%image',$image,$file);
        }

        /** Send emails for 3 and under **/
        $sql_query_select_3_under_location = "SELECT email_3_under FROM location WHERE id = $location AND client_id = ". $globalapp['client_id'];
        $three_and_under = func_query_first($sql_query_select_3_under_location);
        $mails = explode(',',$three_and_under['email_3_under']);
        foreach ($mails as $mail) {
            if (strpos($mail,'mms.att') !== false || strpos($mail,'vtext.com') !== false || strpos($mail,'txt.att') !== false || strpos($mail,'tmomail.net') !== false) {
                debugf("Sent textual message to mms / sms $mail");
                $textsms = 'New review has been submitted. ' . $f['first_name'] . ' ' . $f['lastname'] . ' gave you ' . $rate . ' star(s). Phone number is ' . $f['phone'];
                send_email($mail,'New Review for company',$textsms,$r['senderEmail'],'OQRA EO',true);
            } else {
                send_email($mail,'New Review for company',$file,$r['senderEmail'],'OQRA EO');
            }
        }
    } else {
        $text_for_admin = $f['first_name'] . ' has reviewed your company with ' . $rate . ' stars. <br />Email: ' . $f['email'] . '<br />Phone: ' . $f['phone_np'] . '<br />Review:<br />'.$link;

        $file1 = file_get_contents('templates/3under.php');
        $file1 = str_replace('%text',$text_for_admin,$file1);

        $clogo = func_query_first($sql_query_select_logo_client);
        if ($clogo) {
            $cl = 'http://localhost/mobileOqra/www/logos/' . $clogo['logo'];
            $file1 = str_replace('%sitelogo',$cl,$file1);
        }

        $text = str_replace('%s',$f['first_name'],$r['email_4_5']);
        $text = str_replace('%r',$rate,$text);
        if ($f['email'] != '') {
            $text = str_replace('%e',$f['email'],$text);
        } else {
            $text = str_replace('%e','No email address entered',$text);
        }
        $text = str_replace('%p',$f['phone'],$text);
        $text = str_replace('%l',$f['lastname'],$text);
        $text = str_replace('%t',$link,$text);
        $text = nl2br($text);
        $file = file_get_contents('templates/template.php');
        $file = str_replace('%text',$text,$file);

        $clogo = func_query_first($sql_query_select_logo_client);
        if ($clogo) {
            $cl = 'http://localhost/mobileOqra/www/logos/' . $clogo['logo'];
            $file = str_replace('%sitelogo',$cl,$file);
        }

        /** Text messaging **/
        $sms_4_5 = "Hello,\n" . $f['first_name'] . " submitted new review.\n\nRate $rate stars\n\nPhone: " . $f['phone'] . "\n\nReview:\n\n$link";
        sendSms($r['sms_4_5'],$sms_4_5);

        /** Check image **/
        if ($r['email_img'] != '') {
            $image = "<div class='image' style='font-size: 12px;mso-line-height-rule: at-least;font-style: normal;font-weight: 400;Margin-bottom: 0;Margin-top: 0;font-family: 'Open Sans',sans-serif;color: #60666d;' align='center'>
              <img class='gnd-corner-image gnd-corner-image-center gnd-corner-image-top' style='border: 0;-ms-interpolation-mode: bicubic;display: block;max-width: 900px;' src='".$r['email_img']."' alt='' width='600' height='156' />
            </div>";
            $file = str_replace('%image',$image,$file);
        }

        send_email($f['email'],'Thank you for your review',$file,$r['senderEmail'],$f['first_name']);

        /** Send emails for 3 and under **/
        $sql_query_select_email_3_under = "SELECT * FROM sr_location WHERE id = $location AND client_id = ".$globalapp['client_id'];
        $three_and_under = func_query_first($sql_query_select_email_3_under);
        $mails = explode(',',$three_and_under['email_4_5']);
        foreach ($mails as $mail) {
            if (strpos($mail,'mms.att') !== false || strpos($mail,'vtext.com') !== false || strpos($mail,'txt.att') !== false || strpos($mail,'tmomail.net') !== false) {
                debugf("Sent textual message to mms / sms $mail");
                $textsms = 'New review has been submitted. ' . $f['first_name'] . ' ' . $f['lastname'] . ' gave you ' . $rate . ' star(s). Phone number is ' . $f['phone'];
                send_email($mail,'New Review for company',$textsms,$r['senderEmail'],'OQRA EO',true);
            } else {
                send_email($mail,'New Review for company',$file1,$r['senderEmail'],'OQRA EO');
            }
        }
    }


    echo json_encode(array('status'=>true));
}

function search($globalapp,$query){
    echo '<script>console.log("1")</script>';
    global $db;
    $data = json_decode(file_get_contents("php://input"),true);
    $email = $data['data']['mail'];
    $phone = $data['data']['phone'];
    $email = '';

    if ($phone == '') {
        $sql_query_select_user = "SELECT * FROM user WHERE client_id = ".$globalapp['client_id']." ORDER BY id ASC";
        $results = mysqli_query($this->db->connection,$sql_query_select_user);
        if (mysqli_num_rows($results) == 0) {
            error('No users found in the database!');
        } else {
            $res = array();
            while ($r = mysqli_fetch_array($results,MYSQL_ASSOC)) {
                $res[] = $r;
            }
        }
        echo json_encode(array('status'=>true,'results'=>$res));
        exit;
    }else{
        $sql_query_select_user = "SELECT * FROM user WHERE phone = '$phone' AND client_id = ".$globalapp['client_id']." LIMIT 1";
        $q = $db->query($sql_query_select_user)[0];
        if (!$q) {
            /** Get not found message **/
            $result = $db->query($query["select_client_with_configuration"])[0];
            $result = ($result->not_found_message == '') ? $result = 'We could not find your number' : $result->not_found_message;
            error($result);
        } else {
            echo json_encode(array('status'=>true,'id'=>$q->id,'first_name'=>$q->first_name,'last_name'=>$q->last_name));
        }
    }
}

function uploadvoice($globalapp,$query){
    $data = json_decode(file_get_contents("php://input"),true);
    print_r($data);
}

function send_email($to,$subject,$text,$from,$name = "Review", $mobile = false) {
    if ($to == '') return false;

    $transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, "ssl")
        ->setUsername('peradms.app@gmail.com')
        ->setPassword('dmssapien!2016');
    $mailer = Swift_Mailer::newInstance($transport);

    if (!$mobile) {
        $message = Swift_Message::newInstance($subject)
            ->setFrom(array('peradms.app@gmail.com' => $name))
            ->setTo(array($to))
            ->setReplyTo(array('no-reply@1qreputation.com' => '1qreputation.com'))
            ->setContentType("text/html")
            ->setBody($text);

    } else {
        $message = Swift_Message::newInstance($subject)
            ->setFrom(array('peradms.app@gmail.com' => 'Review'))
            ->setTo(array($to))
            ->setBody($text);
    }
    $headers = $message->getHeaders();

    $headers->addTextHeader('X-Mailer', 'SwiftMailer 5.4.3');
    /*
    foreach ($headers->getAll() as $header) {
      echo var_dump($header->toString());
    }
    die;
    */
    $result = $mailer->send($message);


    /*
    $mail = new SimpleMail();

    if (!$mobile) {
        $mail->setTo($to, $name)
             ->setSubject($subject)
             ->setFrom('reviews@1qreputation.com', 'Review')
             ->addMailHeader('Reply-To', 'no-reply@1qreputation.com', '1qreputation.com')
             ->addGenericHeader('X-Mailer', 'PHP/' . phpversion())
             ->addGenericHeader('Content-Type', 'text/html; charset="utf-8"')
             ->setMessage($text);
        $send = $mail->send();
    } else {
        $mail->setTo($to, $name)
             ->setSubject($subject)
             ->setFrom('reviews@1qreputation.com', 'Review')
             ->addGenericHeader('X-Mailer', 'PHP/' . phpversion())
             ->setMessage($text);
        $send = $mail->send();
    }
    */
}

function sendMobile($to,$text,$name = "Review") {
    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    mail($to, "New Review", $text, "From: Review <reviews@1qreputation.com>\r\n$headers");

    $transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, "ssl")
        ->setUsername('peradms.app@gmail.com')
        ->setPassword('dmssapien!2016');
    $mailer = Swift_Mailer::newInstance($transport);
    $message = Swift_Message::newInstance('New Review')
        ->setFrom(array('peradms.app@gmail.com' => $name))
        ->setTo(array($to))
        ->setBody($text);
}

function sendSms($to = '',$msg = '', $single = false) {
    if ($to == '' && empty($msg)) return false;

    require_once('../cadmin/sms/Twilio.php');
    global $sid,$token;
    $client = new Services_Twilio($sid, $token);

    if ($single) {
        if (strpos($to,'+') === false) {
            $to = '+1' . $to;
        }
        $to = str_replace(array('(',')','-',' '),'',$to);
        debugf("Sending single notification message to $to | Message: $msg");
        $message = $client->account->messages->sendMessage(
            '18317776772',
            $to,
            $msg
        );
        if ($message->sid) {
            debugf("Sent sms to $to SID " . $message->sid);
        } else {
            debugf("SMS sending failed for $to");
        }
        return;
    }

    $numbers = explode(',',$to);
    if (count($numbers) == 0) return;
    foreach ($numbers as $number) {
        if (strpos($number,'+') === false) {
            $number = '+1' . $number;
        }
        $number = str_replace(array('(',')','-',' '),'',$number);
        debugf("Sending multiple notification message to $number | Message: $msg");
        $message = $client->account->messages->sendMessage(
            '18317776772',
            $number,
            $msg
        );
        if ($message->sid) {
            debugf("Sent sms to $number SID " . $message->sid);
        } else {
            debugf("SMS sending failed for $number");
        }
    }
}

/** Error function **/
function error($msg) {
    echo json_encode(array('status'=>false,'msg'=>$msg));
    exit;
}

function func_query_first($sql){
    global $db;
    $result = $db->query($sql);
    if(!empty($result)){
        return $row = mysqli_fetch_array($result,MYSQL_ASSOC);
    }else{
        return $row = false;
    }
}

function func_query($sql) {
    global $db;
    $myArray = array();
    $result = $db->query($sql);
    if(!empty($result)){
        while($row = mysqli_fetch_array($result, MYSQL_ASSOC)){
            $myArray[] = $row;
        }
        return $myArray;
    }
    else
    {
        return false;
    }
}


die();

?>