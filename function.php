<?php 
// functions.php
if (session_status() === PHP_SESSION_NONE) session_start();

function getIndianCurrency(float $number){$decimal=round($number-($no=floor($number)),2)*100;$hundred=null;$digits_length=strlen($no);$i=0;$str=array();$words=array(0=>'',1=>'One',2=>'Two',3=>'Three',4=>'Four',5=>'Five',6=>'Six',7=>'Seven',8=>'Eight',9=>'Nine',10=>'Ten',11=>'Eleven',12=>'Twelve',13=>'Thirteen',14=>'Fourteen',15=>'Fifteen',16=>'Sixteen',17=>'Seventeen',18=>'Eighteen',19=>'Nineteen',20=>'Twenty',30=>'Thirty',40=>'Forty',50=>'Fifty',60=>'Sixty',70=>'Seventy',80=>'Eighty',90=>'Ninety');$digits=array('','Hundred','Thousand','Lakh','Crore');while($i<$digits_length){$divider=($i==2)?10:100;$number=floor($no%$divider);$no=floor($no/$divider);$i+=$divider==10?1:2;if($number){$plural=(($counter=count($str))&&$number>9)?'s':null;$hundred=($counter==1&&$str[0])?' and ':null;$str[]=($number<21)?$words[$number].' '.$digits[$counter].$plural.' '.$hundred:$words[floor($number/10)*10].' '.$words[$number%10].' '.$digits[$counter].$plural.' '.$hundred;}else{$str[]=null;}}$Rupees=implode('',array_reverse($str));$paise=($decimal>0)?".".($words[floor($decimal/10)*10]." ".$words[$decimal%10]).' Paise':'';return($Rupees?$Rupees.' Rupees ':'').$paise;}





/**
 * send_sms: send SMS via a generic API
 * Replace $api_url, $api_key and payload structure to match your provider.
 */
function send_sms($to, $message) {
    // Placeholder - replace with your SMS provider details
    $api_url = "https://api.example-sms.com/send";
    $api_key = "YOUR_API_KEY_HERE";

    $data = [
        'to' => $to,
        'message' => $message,
        'apikey' => $api_key
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'error' => $err];
    } else {
        // Optionally inspect $response for provider success/failure
        return ['success' => true, 'response' => $response];
    }
}

/**
 * get_login_attempts: returns attempts row for username or user_id
 */
function get_login_attempts($conn, $username = null, $user_id = null) {
    if ($user_id) {
        $stmt = $conn->prepare("SELECT * FROM login_attempts WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM login_attempts WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

/**
 * increment_failed_attempt: increment attempts and set blocked_until if threshold reached
 * threshold default 5, block_minutes default 15
 */
function increment_failed_attempt($conn, $username = null, $user_id = null, $threshold = 5, $block_minutes = 15) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $row = get_login_attempts($conn, $username, $user_id);

    if ($row) {
        $attempts = $row['attempts'] + 1;
        $blocked_until = null;
        if ($attempts >= $threshold) {
            $blocked_until = date('Y-m-d H:i:s', strtotime("+{$block_minutes} minutes"));
        }
        $stmt = $conn->prepare("UPDATE login_attempts SET attempts = ?, last_attempt = NOW(), blocked_until = ?, ip_address = ? WHERE id = ?");
        $stmt->bind_param("issi", $attempts, $blocked_until, $ip, $row['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $attempts = 1;
        $blocked_until = null;
        if ($attempts >= $threshold) {
            $blocked_until = date('Y-m-d H:i:s', strtotime("+{$block_minutes} minutes"));
        }
        $stmt = $conn->prepare("INSERT INTO login_attempts (user_id, username, attempts, last_attempt, blocked_until, ip_address) VALUES (?, ?, ?, NOW(), ?, ?)");
        $nullableUserId = $user_id ?: null;
        $stmt->bind_param("isiss", $nullableUserId, $username, $attempts, $blocked_until, $ip);
        $stmt->execute();
        $stmt->close();
    }
    return $attempts;
}

/**
 * reset_login_attempts: clear attempts (on successful login)
 */
function reset_login_attempts($conn, $username = null, $user_id = null) {
    if ($user_id) {
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ?");
        $stmt->bind_param("s", $username);
    }
    $stmt->execute();
    $stmt->close();
}

/**
 * is_blocked: check if currently blocked by username or user_id
 */
function is_blocked($conn, $username = null, $user_id = null) {
    $row = get_login_attempts($conn, $username, $user_id);
    if ($row && $row['blocked_until']) {
        $now = new DateTime();
        $blocked_until = new DateTime($row['blocked_until']);
        if ($blocked_until > $now) {
            return $row['blocked_until'];
        }
    }
    return false;
}
