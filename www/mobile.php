<?php
Header('Access-Control-Allow-Origin: *');

/**
 * Smoke APP reputation mobile api
 * v.0.1
 * @copyright dmsapiens.com
 */
error_reporting(0);
ini_set('display_error','Off');

$sid = 'AC757ca2e4dd9cba61ac6b138785ea6405';
$token = 'e905c5307d4beffb60ff3409330e4a48';

function debugf($text) {
    $myfile = fopen("debug.txt", "a");
    $date = date("d/m/Y H:i:s");
    fwrite($myfile,"DEBUG $date : $text\r\n");
    fclose($myfile);
}

$base_url = 'http://dashboard.1qreputation.com';

$servername = 'localhost';
$username = '';
$password = '';
$db = '';

require_once 'vendor/swiftmailer/swiftmailer/lib/swift_required.php';
//send_email('aleksandar.stojilkovic@gmail.com','Test','Text poruke','peraapp.dms@gmail.com', "OKRA EO", $mobile = false);

/** Error function **/
function error($msg) {
    echo json_encode(array('status'=>false,'msg'=>$msg));
    exit;
}

function func_query_first($sql){
    global $con;
    $result = mysqli_query($con,$sql);
	if(!empty($result)){
	   return $row = mysqli_fetch_array($result,MYSQL_ASSOC);
	}else{
		return $row = false;
	}
}

function func_query($sql) {
    global $con;
    $myArray = array();
	$result = mysqli_query($con,$sql);
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

$con = mysqli_connect($servername, $username, $password,$db);

if (mysqli_connect_error()) {
    error('Could not connect to database, please try again!');
}

$client_id = 5;

if ($_GET['route'] == 'err') {
    $data = json_decode(file_get_contents("php://input"),true);
    $err = $data['data'];
    $myfile = fopen("testfile.txt", "a");
    fwrite($myfile,"DEBUG: $err\r\n");
    fclose($myfile);
}

/**
 * Search for closest store
 */
if ($_GET['route'] == 'closest') {
    $data = json_decode(file_get_contents("php://input"),true);
    $origLon = $data['data']['lon'];
    $origLat = $data['data']['lat'];
    
    $dist = 50000; // miles
    $tableName = 'sr_locations';
    $query = "SELECT id, name, address1, city, zipcode, state, latitude, longitude, 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - latitude)*pi()/180/2),2)+COS($origLat*pi()/180 )*COS(latitude*pi()/180)*POWER(SIN(($origLon-longitude)*pi()/180/2),2))) as distance FROM $tableName WHERE client_id = $client_id AND longitude between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat))*69) and latitude between ($origLat-($dist/69)) and ($origLat+($dist/69)) having distance < $dist ORDER BY distance limit 3";
    
    $q = func_query($query);
    
    if (!$q) {
        $res = func_query("SELECT * FROM $tableName WHERE client_id = $client_id ORDER BY id ASC");
        echo json_encode(array('status'=>false,'msg'=>'No locations found within radius of ' . $dist . ' miles from your current location. Sorry, we can\'t continue.','results'=>$res));
    } else {
        $final = array();
        foreach ($q as $r) {
            $final[] = array('id'=>$r['id'],'name'=>$r['name'],'address'=>$r['address1'],'city'=>$r['city'],'zip'=>$r['zipcode'],'state'=>$r['state']);
        }
        echo json_encode(array('status'=>true,'dist'=>$dist,'results'=>$final));
    }
    exit;
}

if ($_GET['route'] == 'fallbackgps') {
    $tableName = 'sr_locations';
    $query = "SELECT * FROM $tableName WHERE client_id = $client_id ORDER BY id DESC";
    
    $q = func_query($query);
    
    if (!$q) {
        $res = func_query("SELECT * FROM $tableName WHERE client_id = $client_id ORDER BY id ASC");
        echo json_encode(array('status'=>false,'msg'=>'No locations found. Sorry, we can\'t continue.','results'=>$res));
    } else {
        $final = array();
        foreach ($q as $r) {
            $final[] = array('id'=>$r['id'],'name'=>$r['name'],'address'=>$r['address1'],'city'=>$r['city'],'zip'=>$r['zipcode'],'state'=>$r['state']);
        }
        echo json_encode(array('status'=>true,'dist'=>$dist,'results'=>$final));
    }
    exit;
}

/**
 * New user routine
 */
if ($_GET['route'] == 'new') {
    $data = json_decode(file_get_contents("php://input"),true);
    $firstname = $data['data']['Firstname'];
    $lastname = $data['data']['Lastname'];
    $email = $data['data']['Email'];
    $cell = $data['data']['Cell'];
    
    /** Already exists message **/
    $notMessage = func_query_first("SELECT exists_msg FROM sr_clients WHERE id = $client_id");
    $notMessage = ($notMessage['exists_msg'] == '') ? $notMessage = 'This number already exits. Do you want to make a search?' : $notMessage['exists_msg'];
    
    $check = func_query_first("SELECT 1 FROM sr_newuser WHERE phone_no = '$cell' AND client_id = $client_id");
    if ($check) {
        echo json_encode(array('status'=>false,'msg'=>$notMessage));
        exit;
    }
    
    mysqli_query($con,"INSERT INTO sr_newuser (firstname,lastname,phone_no,emailid,is_delete,client_id) VALUES ('$firstname','$lastname','$cell','$email','0','$client_id')");
    
    $id = mysqli_insert_id($con);
    
    echo json_encode(array('status'=>true,'id'=>$id));
    exit;
}

/**
 * Dissatisfied routine
 */
if ($_GET['route'] == 'dissatisfied') {
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
    exit;
}

/**
 * Rate routine
 */
if ($_GET['route'] == 'rateit') {
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $date = date('Y-m-d H:i:s');
    $session_id = md5(time());
    $rating_id = $data['data']['ratingid'];
    
    mysqli_query($con,"INSERT INTO sr_rating (user_id,rating,rating_no,session_id,entry_date,is_delete,client_id) VALUES ('$userid','$rate','$rate','$session_id','$date','0','$client_id')");
    $rating_id = mysqli_insert_id($con);
    
    echo json_encode(array('status'=>true,'id'=>$rating_id,'session'=>$session_id));
    exit;
}

/**
 * Log share route
 */
if ($_GET['route'] == 'logshare') {
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $type = $data['data']['type'];
    $location = $data['data']['location'];
    $date = date('Y-m-d H:i:s');
    
    mysqli_query($con,"INSERT INTO sr_socialshare (user_id,social_type,rating,entry_date,location,client_id) VALUES ('$userid','$type','$rate','$date','$location','$client_id')");
    
    echo json_encode(array('status'=>true));
    exit;
}

/**
 * Send Social Sms
 */
if ($_GET['route'] == 'sendsocialsms') {
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $location = $data['data']['location'];
    $network = $data['data']['network'];
    $date = date('Y-m-d H:i:s');
    
    mysqli_query($con,"INSERT INTO sr_socialshare (user_id,social_type,rating,entry_date,location,client_id) VALUES ('$userid','$network','$rate','$date','$location','$client_id')");
    
    /** Get client phone number **/
    $send = false;
    $cnum = func_query_first("SELECT phone_no FROM sr_newuser WHERE client_id = $client_id AND id = $userid");
    if ($cnum) {
        /** Get review **/
        $review = func_query_first("SELECT review,rating_id FROM sr_review WHERE client_id = $client_id AND location = $location AND user_id = $userid ORDER BY id DESC LIMIT 1");
        if ($review) {
            debugf("Prepare SMS for " . $cnum['phone_no']. " Type of review: " . $review['review']);
            if ($review['review'] == 'Upload') {
                $c = func_query_first("SELECT file FROM sr_reviews WHERE client_id = $client_id AND userId = $userid AND rating_id = " . $review['rating_id'] . " ORDER BY id LIMIT 1");
                if ($c) {
                    if (strpos($c['file'],'MOV') !== false) {
                        $textReview = 'http://dashboard.1qreputation.com/uploads/' . $c['file'];
                    } else if (strpos($c['file'],'mp4') !== false) {
                        $tm = basename($c['file']);
                        $textReview = 'http://dashboard.1qreputation.com/uploads/' . $tm;
                    } else {
                        $textReview = 'http://1qreputation.com/audioDownload.php?name=' . basename($c['file']) . '.mp3';
                    }
                    $send = true;
                }
            } else {
                $textReview = func_query_first("SELECT review FROM sr_review WHERE user_id = $userid AND client_id = $client_id AND location = $location ORDER BY id DESC LIMIT 1");
                if ($textReview) {
                    $textReview = $textReview['review'];
                    $send = true;
                }
            }
        }
    }
    
    /** Gplus link **/
    $glink = func_query_first("SELECT link FROM sr_social WHERE client_id = $client_id AND location_id = $location AND active = 1 AND name = '$network' LIMIT 1");
    if ($glink && $send) {
        $gpluslink = $glink['link'];
        debugf("Send sms is true for " . $cnum['phone_no']);
        sendSms($cnum['phone_no'],"Thanks for your review.\n\nPlease COPY your review:\n$textReview\n\nCLICK & PASTE below:\n$gpluslink",true);
    }
    
    echo json_encode(array('status'=>true));
    exit;
}

/**
 * Send SMS
 */
if ($_GET['route'] == 'sendsms') {
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['rate'];
    $location = $data['data']['location'];
    $date = date('Y-m-d H:i:s');
    
    mysqli_query($con,"INSERT INTO sr_socialshare (user_id,social_type,rating,entry_date,location,client_id) VALUES ('$userid','google','$rate','$date','$location','$client_id')");

    /** Get client phone number **/
    $send = false;
    $cnum = func_query_first("SELECT phone_no FROM sr_newuser WHERE client_id = $client_id AND id = $userid");
    if ($cnum) {
        /** Get review **/
        $review = func_query_first("SELECT review,rating_id FROM sr_review WHERE client_id = $client_id AND location = $location AND user_id = $userid ORDER BY id DESC LIMIT 1");
        if ($review) {
            debugf("Prepare SMS for " . $cnum['phone_no']. " Type of review: " . $review['review']);
            if ($review['review'] == 'Upload') {
                $c = func_query_first("SELECT file FROM sr_reviews WHERE client_id = $client_id AND userId = $userid AND rating_id = " . $review['rating_id'] . " ORDER BY id LIMIT 1");
                if ($c) {
                    if (strpos($c['file'],'MOV') !== false) {
                        $textReview = 'http://dashboard.1qreputation.com/uploads/' . $c['file'];
                    } else if (strpos($c['file'],'mp4') !== false) {
                        $tm = basename($c['file']);
                        $textReview = 'http://dashboard.1qreputation.com/uploads/' . $tm;
                    } else {
                        $textReview = 'http://1qreputation.com/audioDownload.php?name=' . basename($c['file']) . '.mp3';
                    }
                    $send = true;
                }
            } else {
                $textReview = func_query_first("SELECT review FROM sr_review WHERE user_id = $userid AND client_id = $client_id AND location = $location ORDER BY id DESC LIMIT 1");
                if ($textReview) {
                    $textReview = $textReview['review'];
                    $send = true;
                }
            }
        }
    }
    
    /** Gplus link **/
    $glink = func_query_first("SELECT link FROM sr_social WHERE client_id = $client_id AND location_id = $location AND active = 1 AND name = 'google' LIMIT 1");
    if ($glink && $send) {
        $gpluslink = $glink['link'];
        debugf("Send sms is true for " . $cnum['phone_no']);
        sendSms($cnum['phone_no'],"Thanks for your review.\n\nPlease COPY your review:\n$textReview\n\nCLICK & PASTE below:\n$gpluslink",true);
    }
    
    echo json_encode(array('status'=>true));
    exit;
}

/**
 * Review routine
 */
if ($_GET['route'] == 'submitreview') {
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $rate = $data['data']['your_rate'];
    $review = $data['data']['comment'];
    $location = $data['data']['location'];
    $session_id = md5(time() + rand(1,1111111));
    $date = date('Y-m-d H:i:s');
    
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
    $results = mysqli_query($con,"SELECT senderEmail,emails3,4_5_mail,3_under_mail,email_img,email_img1,sms_123,sms_45 FROM sr_locations WHERE id = $location AND client_id = $client_id");
    $r = mysqli_fetch_array($results,MYSQL_ASSOC);
    sendSms($r['sms_132'],'Test');
    sendSms($r['sms_45'],'Test');
    $cnum = func_query_first("SELECT phone_no FROM sr_newuser WHERE client_id = $client_id AND id = $userid");
    sendSms($cnum['phone_no'],'Test',true);
    echo json_encode(array('status'=>true));
    exit;*/
    

    /** Insert data **/
    mysqli_query($con,"INSERT INTO sr_rating (user_id,rating,rating_no,session_id,entry_date,is_delete,location,client_id) VALUES ('$userid','$rate','$rate','$session_id','$date','0','$location','$client_id')");
    $rating_id = mysqli_insert_id($con);
    
    /** Insert rating **/
    $status = ($rate < 4) ? 0 : 1;
    
    $review = mysqli_real_escape_string($con,$review);
    
    mysqli_query($con,"INSERT INTO sr_review (user_id,review,rating_id,rating_no,status,location,client_id) VALUES ('$userid','$review','$rating_id','$rate','$status','$location','$client_id')");
    
    debugf("User id: $userid submitted text review at $date");
    
    /** Send email **/
    $results = mysqli_query($con,"SELECT senderEmail,emails3,4_5_mail,3_under_mail,email_img,email_img1,sms_123,sms_45 FROM sr_locations WHERE id = $location AND client_id = $client_id");
    $r = mysqli_fetch_array($results,MYSQL_ASSOC);
    $email = $r['email'];
    
    /** Get firstname of customer **/
    $fname = mysqli_query($con,"SELECT firstname,lastname,emailid,phone_no FROM sr_newuser WHERE id = $userid AND client_id = $client_id");
    $f = mysqli_fetch_array($fname,MYSQL_ASSOC);
    
    if ($rate <= 3) {
        debugf('Sending written review for ' . $rate . ' stars - Location: ' . $location);
        $text = str_replace('%s',$f['firstname'],$r['3_under_mail']);
        $text = str_replace('%r',$rate,$text);
        if ($f['emailid'] != '') {
            $text = str_replace('%e',$f['emailid'],$text);
        } else {
            $text = str_replace('%e','No email address entered',$text);
        }
        $text = str_replace('%p',$f['phone_no'],$text);
        $text = str_replace('%l',$f['lastname'],$text);
        $text = str_replace('%t',nl2br($review),$text);
        $text = nl2br($text);
        $file = file_get_contents('templates/3under.php');
        $file = str_replace('%text',$text,$file);
        
        $clogo = func_query_first("SELECT logo FROM sr_clients WHERE client_id = $client_id");
        if ($clogo) {
            $cl = 'http://dashboard.1qreputation.com/logos/' . $clogo['logo'];
            $file = str_replace('%sitelogo',$cl,$file);
        }
        
        /** Text messaging **/
        $smsText = str_replace('%s',$f['firstname'],$r['3_under_mail']);
        $smsText = str_replace('%r',$rate,$smsText);
        if ($f['emailid'] != '') {
            $smsText = str_replace('%e',$f['emailid'],$smsText);
        }
        $smsText = str_replace('%p',$f['phone_no'],$smsText);
        $smsText = str_replace('%l',$f['lastname'],$smsText);
        $revCopy = str_replace('\n',' ',$review);
        $smsText = str_replace('%t',$revCopy,$smsText);
        
        sendSms($r['sms_123'],$smsText);
        
        /** Mobile text **/
        $mobile = false;
        $mobile = $r['3_under_mail'];
        $mobile = str_replace('%s',$f['firstname'],$mobile);
        $mobile = str_replace('%r',$rate,$mobile);
        if ($f['emailid'] != '') {
            $mobile = str_replace('%e',$f['emailid'],$mobile);
        } else {
            $mobile = str_replace('%e','No email address entered',$mobile);
        }
        $mobile = str_replace('%p',$f['phone_no'],$mobile);
        $mobile = str_replace('%l',$f['lastname'],$mobile);
        $mobile = str_replace('%t',$review,$mobile);
        
        /** Check image **/
        if ($r['email_img1'] != '') {
            $image = "<div class='image' style='font-size: 12px;mso-line-height-rule: at-least;font-style: normal;font-weight: 400;Margin-bottom: 0;Margin-top: 0;font-family: 'Open Sans',sans-serif;color: #60666d;' align='center'>
              <img class='gnd-corner-image gnd-corner-image-center gnd-corner-image-top' style='border: 0;-ms-interpolation-mode: bicubic;display: block;max-width: 900px;' src='".$r['email_img1']."' alt='' width='600' height='156' />
            </div>";
            $file = str_replace('%image',$image,$file);
        } else {
            $file = str_replace('%image','',$file);
        }
        
        /** Send emails for 3 and under **/
        $three_and_under = func_query_first("SELECT emails_321 FROM sr_locations WHERE id = $location AND client_id = $client_id");
        $mails = explode(',',$three_and_under['emails_321']);
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
        $text_for_admin = $f['firstname'] . ' has reviewed your company with ' . $rate . ' stars. <br />Email: ' . $f['emailid'] . '<br />Phone: ' . $f['phone_no'] . '<br />Review:<br />'.nl2br($review);
        $file1 = file_get_contents('templates/3under.php');
        $file1 = str_replace('%text',$text_for_admin,$file1);
        
        $clogo = func_query_first("SELECT logo FROM sr_clients WHERE client_id = $client_id");
        if ($clogo) {
            $cl = 'http://dashboard.1qreputation.com/logos/' . $clogo['logo'];
            $file1 = str_replace('%sitelogo',$cl,$file1);
        }
        
        $text = str_replace('%s',$f['firstname'],$r['4_5_mail']);
        $text = str_replace('%r',$rate,$text);
        if ($f['emailid'] != '') {
            $text = str_replace('%e',$f['emailid'],$text);
        } else {
            $text = str_replace('%e','No email address entered',$text);
        }
        $text = str_replace('%p',$f['phone_no'],$text);
        $text = str_replace('%l',$f['lastname'],$text);
        $text = str_replace('%t',nl2br($review),$text);
        $text = nl2br($text);
        $file = file_get_contents('templates/template.php');
        $file = str_replace('%text',$text,$file);
        
        $clogo = func_query_first("SELECT logo FROM sr_clients WHERE client_id = $client_id");
        if ($clogo) {
            $cl = 'http://dashboard.1qreputation.com/logos/' . $clogo['logo'];
            $file = str_replace('%sitelogo',$cl,$file);
        }
        
        /** Text messaging **/
        $sms45 = "Hello,\n" . $f['firstname'] . " submitted new review.\n\nRate $rate stars\n\nPhone: " . $f['phone_no'] . "\n\nReview:\n\n$review";
        sendSms($r['sms_45'],$sms45);
        
        /** Mobile text **/
        $mobile = false;
        $mobile = $r['4_5_mail'];
        $mobile = str_replace('%s',$f['firstname'],$mobile);
        $mobile = str_replace('%r',$rate,$mobile);
        if ($f['emailid'] != '') {
            $mobile = str_replace('%e',$f['emailid'],$mobile);
        } else {
            $mobile = str_replace('%e','No email address entered',$mobile);
        }
        $mobile = str_replace('%p',$f['phone_no'],$mobile);
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
        
        send_email($f['emailid'],'Thank you for your review',$file,$r['senderEmail'],$f['firstname']);
        debugf("Sent review (for customer/patient) email to ".$f['emailid']." for stars $rate - Location Id: $location");
        
        $three_and_under = func_query_first("SELECT emails_45 FROM sr_locations WHERE id = $location AND client_id = $client_id");
        $mails = explode(',',$three_and_under['emails_45']);
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
    exit;
}

/** Load url **/
if ($_GET['route'] == 'loadurl') {
    $data = json_decode(file_get_contents("php://input"),true);
    $network = $data['data']['type'];
    
    if (isset($_GET['lid']) && $_GET['lid'] != '') {
        $loc_id = (int)$_GET['lid'];
    } else {
        $q = func_query_first("SELECT id FROM sr_locations WHERE client_id = $client_id ORDER BY id ASC LIMIT 1");
        $loc_id = $q['id'];
    }
    
    $q = func_query_first("SELECT link,description,logo FROM sr_social WHERE name = '$network' AND location_id = $loc_id AND client_id = $client_id LIMIT 1");
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
    exit;
}

/** Load texts **/
if ($_GET['route'] == 'loaddata') {
    
    if (isset($_GET['lid']) && $_GET['lid'] != '') {
        $loc_id = (int)$_GET['lid'];
    } else {
        /** Get first location **/
        $q = func_query_first("SELECT id FROM sr_locations WHERE client_id = $client_id ORDER BY id ASC LIMIT 1");
        $loc_id = $q['id'];
    }
    
    /** Get timeout **/
    $timeOut = func_query_first("SELECT timeout FROM sr_clients WHERE id = $client_id");
    $timeOut = ($timeOut['timeout'] == '') ? 0 : ($timeOut['timeout'] * 60);
    
    /** Get question **/
    $question = func_query_first("SELECT question FROM sr_clients WHERE id = $client_id");
    $question = ($question['question'] == '') ? 'How did you find your experience today?' : $question['question'];
    
    $results = mysqli_query($con,"SELECT * FROM sr_locations WHERE id = $loc_id AND client_id = $client_id");
    $r = mysqli_fetch_array($results,MYSQL_ASSOC);
    
    $social = false;
    $google = false;
    $social_networks = mysqli_query($con,"SELECT name,active FROM sr_social WHERE active = 1 AND location_id = $loc_id AND client_id = $client_id ORDER BY name ASC");
    if (mysqli_num_rows($social_networks) > 0) {
        while ($row = $social_networks->fetch_assoc()) {
            if ($row['name'] == 'google' && $row['active'] == 1) $google = true;
            $social[] = $row['name'];
        }
    }
    
    $q = func_query_first("SELECT COUNT(*) AS total, id FROM sr_locations WHERE client_id = $client_id");
    if ($q['total'] == 1) {
        $locations = 1;
        $location_id = $q['id'];
    } else {
        $locations = $q['total'];
        $location_id = false;
    }
    
    echo json_encode(array('status'=>true,'google'=>$google,'timeout'=>$timeOut,'question'=>$question,'data'=>$r,'social'=>$social,'url'=>$base_url,'total'=>$locations,'location_id'=>$location_id));
    exit;
}

/**
 * Finish
 */
if ($_GET['route'] == 'finish') {
    $data = json_decode(file_get_contents("php://input"),true);
    $userid = $data['data']['userid'];
    $file = $data['data']['file'];
    $rate = $data['data']['your_rate'];
    $location = $data['data']['location'];
    $session_id = md5(time() + rand(1,1111111));
    $date = date('Y-m-d H:i:s');
    $date1 = time();
    
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
        $link = 'http://dashboard.1qreputation.com/uploads/' . basename($file);
    } elseif ($ext == 'm4a') {
        $link = 'http://1qreputation.com/audioDownload.php?name=' . basename($back) . '.mp3';
    } elseif ($ext == '3gp') {
        $link = 'http://dashboard.1qreputation.com/done/' . basename($back) . '.mp4';
    } elseif ($ext == 'mp4') {
        $link = 'http://dashboard.1qreputation.com/uploads/' . basename($back);
    }
    
    /** Insert data **/
    mysqli_query($con,"INSERT INTO sr_rating (user_id,rating,rating_no,session_id,entry_date,is_delete,location,client_id) VALUES ('$userid','$rate','$rate','$session_id','$date','0','$location','$client_id')");
    $rating_id = mysqli_insert_id($con);
    
    /** Insert rating **/
    if ($rate < 4) {
        $status = 0;
    } else {
        $status = 1;
    }
    mysqli_query($con,"INSERT INTO sr_review (user_id,review,rating_id,rating_no,status,location,client_id) VALUES ('$userid','Upload','$rating_id','$rate','$status','$location','$client_id')");
    
    mysqli_query($con,"INSERT INTO sr_reviews (userId,file,date,rating_id,client_id) VALUES ('$userid','$file','$date1','$rating_id','$client_id')");
    
    /** Send email **/
    $results = mysqli_query($con,"SELECT senderEmail,emails3,4_5_mail,3_under_mail,email_img,email_img1,sms_45 FROM sr_locations WHERE id = $location AND client_id = $client_id");
    $r = mysqli_fetch_array($results,MYSQL_ASSOC);
    $email = $r['email'];
    
    /** Get firstname of customer **/
    $fname = mysqli_query($con,"SELECT firstname,lastname,emailid,phone_no FROM sr_newuser WHERE id = $userid AND client_id = $client_id");
    $f = mysqli_fetch_array($fname,MYSQL_ASSOC);
    
    if ($rate <= 3) {
        $text = str_replace('%s',$f['firstname'],$r['3_under_mail']);
        $text = str_replace('%r',$rate,$text);
        if ($f['emailid'] != '') {
            $text = str_replace('%e',$f['emailid'],$text);
        } else {
            $text = str_replace('%e','No email address entered',$text);
        }
        $text = str_replace('%p',$f['phone_no'],$text);
        $text = str_replace('%l',$f['lastname'],$text);
        $text = str_replace('%t',$link,$text);
        $text = nl2br($text);
        $file = file_get_contents('templates/3under.php');
        $file = str_replace('%text',$text,$file);
        
        $clogo = func_query_first("SELECT logo FROM sr_clients WHERE client_id = $client_id");
        if ($clogo) {
            $cl = 'http://dashboard.1qreputation.com/logos/' . $clogo['logo'];
            $file = str_replace('%sitelogo',$cl,$file);
        }
        
        /** Text messaging **/
        $smsText = str_replace('%s',$f['firstname'],$r['3_under_mail']);
        $smsText = str_replace('%r',$rate,$smsText);
        if ($f['emailid'] != '') {
            $smsText = str_replace('%e',$f['emailid'],$smsText);
        }
        $smsText = str_replace('%p',$f['phone_no'],$smsText);
        $smsText = str_replace('%l',$f['lastname'],$smsText);
        $smsText = str_replace('%t',$link,$smsText);
        
        sendSms($r['sms_123'],$smsText);
        
        /** Check image **/
        if ($r['email_img1'] != '') {
            $image = "<div class='image' style='font-size: 12px;mso-line-height-rule: at-least;font-style: normal;font-weight: 400;Margin-bottom: 0;Margin-top: 0;font-family: 'Open Sans',sans-serif;color: #60666d;' align='center'>
              <img class='gnd-corner-image gnd-corner-image-center gnd-corner-image-top' style='border: 0;-ms-interpolation-mode: bicubic;display: block;max-width: 900px;' src='".$r['email_img1']."' alt='' width='600' height='156' />
            </div>";
            $file = str_replace('%image',$image,$file);
        }
        
        /** Send emails for 3 and under **/
        $three_and_under = func_query_first("SELECT emails_321 FROM sr_locations WHERE id = $location AND client_id = $client_id");
        $mails = explode(',',$three_and_under['emails_321']);
        foreach ($mails as $mail) {
            if (strpos($mail,'mms.att') !== false || strpos($mail,'vtext.com') !== false || strpos($mail,'txt.att') !== false || strpos($mail,'tmomail.net') !== false) {
                debugf("Sent textual message to mms / sms $mail");
                $textsms = 'New review has been submitted. ' . $f['firstname'] . ' ' . $f['lastname'] . ' gave you ' . $rate . ' star(s). Phone number is ' . $f['phone_no'];
                send_email($mail,'New Review for company',$textsms,$r['senderEmail'],'OQRA EO',true);
            } else {
                send_email($mail,'New Review for company',$file,$r['senderEmail'],'OQRA EO');
            }
        }
    } else {
        $text_for_admin = $f['firstname'] . ' has reviewed your company with ' . $rate . ' stars. <br />Email: ' . $f['emailid'] . '<br />Phone: ' . $f['phone_np'] . '<br />Review:<br />'.$link;
        
        $file1 = file_get_contents('templates/3under.php');
        $file1 = str_replace('%text',$text_for_admin,$file1);
        
        $clogo = func_query_first("SELECT logo FROM sr_clients WHERE client_id = $client_id");
        if ($clogo) {
            $cl = 'http://dashboard.1qreputation.com/logos/' . $clogo['logo'];
            $file1 = str_replace('%sitelogo',$cl,$file1);
        }
        
        $text = str_replace('%s',$f['firstname'],$r['4_5_mail']);
        $text = str_replace('%r',$rate,$text);
        if ($f['emailid'] != '') {
            $text = str_replace('%e',$f['emailid'],$text);
        } else {
            $text = str_replace('%e','No email address entered',$text);
        }
        $text = str_replace('%p',$f['phone_no'],$text);
        $text = str_replace('%l',$f['lastname'],$text);
        $text = str_replace('%t',$link,$text);
        $text = nl2br($text);
        $file = file_get_contents('templates/template.php');
        $file = str_replace('%text',$text,$file);
        
        $clogo = func_query_first("SELECT logo FROM sr_clients WHERE client_id = $client_id");
        if ($clogo) {
            $cl = 'http://dashboard.1qreputation.com/logos/' . $clogo['logo'];
            $file = str_replace('%sitelogo',$cl,$file);
        }
        
        /** Text messaging **/
        $sms45 = "Hello,\n" . $f['firstname'] . " submitted new review.\n\nRate $rate stars\n\nPhone: " . $f['phone_no'] . "\n\nReview:\n\n$link";
        sendSms($r['sms_45'],$sms45);
        
        /** Check image **/
        if ($r['email_img'] != '') {
            $image = "<div class='image' style='font-size: 12px;mso-line-height-rule: at-least;font-style: normal;font-weight: 400;Margin-bottom: 0;Margin-top: 0;font-family: 'Open Sans',sans-serif;color: #60666d;' align='center'>
              <img class='gnd-corner-image gnd-corner-image-center gnd-corner-image-top' style='border: 0;-ms-interpolation-mode: bicubic;display: block;max-width: 900px;' src='".$r['email_img']."' alt='' width='600' height='156' />
            </div>";
            $file = str_replace('%image',$image,$file);
        }
        
        send_email($f['emailid'],'Thank you for your review',$file,$r['senderEmail'],$f['firstname']);
        
        /** Send emails for 3 and under **/
        $three_and_under = func_query_first("SELECT emails_45 FROM sr_locations WHERE id = $location AND client_id = $client_id");
        $mails = explode(',',$three_and_under['emails_45']);
        foreach ($mails as $mail) {
            if (strpos($mail,'mms.att') !== false || strpos($mail,'vtext.com') !== false || strpos($mail,'txt.att') !== false || strpos($mail,'tmomail.net') !== false) {
                debugf("Sent textual message to mms / sms $mail");
                $textsms = 'New review has been submitted. ' . $f['firstname'] . ' ' . $f['lastname'] . ' gave you ' . $rate . ' star(s). Phone number is ' . $f['phone_no'];
                send_email($mail,'New Review for company',$textsms,$r['senderEmail'],'OQRA EO',true);
            } else {
                send_email($mail,'New Review for company',$file1,$r['senderEmail'],'OQRA EO');
            }
        }
    }
    
    
    echo json_encode(array('status'=>true));
    exit;
}

/**
 * Find routine
 */
if ($_GET['route'] == 'search') {
    $data = json_decode(file_get_contents("php://input"),true);
    $email = $data['data']['mail'];
    $phone = $data['data']['phone'];
    $email = '';
    
    /** Get not found message **/
    $notMessage = func_query_first("SELECT not_found_msg FROM sr_clients WHERE id = $client_id");
    $notMessage = ($notMessage['not_found_msg'] == '') ? $notMessage = 'We could not find your number' : $notMessage['not_found_msg'];
    
    if ($phone == '') {
        $results = mysqli_query($con,"SELECT * FROM sr_newuser WHERE client_id = $client_id ORDER BY id ASC");
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
    }
    
    /** Direct redirect **/
    if ($phone != '') {
        $q = func_query_first("SELECT * FROM sr_newuser WHERE phone_no = '$phone' AND client_id = $client_id LIMIT 1");
        if (!$q) {
            error($notMessage);
        } else {
            echo json_encode(array('status'=>true,'id'=>$q['id'],'firstname'=>$q['firstname'],'lastname'=>$q['lastname']));
            exit;
        }
    }

}

/** Hande upload voice **/
if ($_GET['route'] == 'uploadvoice') {
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