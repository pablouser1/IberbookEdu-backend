<?php
// Get pic and vid of user
require_once("../headers.php");
require_once("../functions.php");
require_once("../auth.php");
require_once("../helpers/db.php");
require_once("../config/config.php");

switch ($_GET["media"]) {
    case "photo":
    case "video":
        $media = $_GET["media"];
    break;
    default:
        die("Ese tipo de archivo no existe");
}

$db = new DB;
$auth = new Auth;
if ($userinfo = $auth->isUserLoggedin()) {
    $stmt = $db->prepare("SELECT $media, id, `type` FROM users WHERE id=?");
    $stmt->bind_param("i", $_GET["id"]);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($medianame, $mediaid, $type);
    $stmt->fetch();
    $downloadable = false;
    if ($stmt->num_rows == 1) {
        // Check if ids match or user is admin
        if ($mediaid == $userinfo["id"] || $auth->isUserAdmin($userinfo)){
            $downloadable = true;
        }
        else{
            die("No tienes permisos para descargar eso");
        }
    }
    else{
        die("No se ha podido encontrar los datos que solicitaste");
    }
    if ($downloadable){
        $filepath = $uploadpath.$userinfo["schoolid"]."/".$userinfo["year"]."/{$type}/{$mediaid}/{$medianame}";
        if(file_exists($filepath)){
            // https://www.sitepoint.com/community/t/loading-html5-video-with-php-chunks-or-not/350957
            $fp = @fopen($filepath, 'rb');
            $size = filesize($filepath); // File size
            $length = $size; // Content length
            $start = 0; // Start byte
            $end = $size - 1; // End byte
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            header('Content-Type: ' . finfo_file($finfo, $filepath));
            finfo_close($finfo);
            header('Content-Disposition: inline; filename="'.basename($filepath).'"');
            //header("Accept-Ranges: 0-$length");
            header("Accept-Ranges: bytes");
            if (isset($_SERVER['HTTP_RANGE'])){
                $c_start = $start;
                $c_end = $end;
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if (strpos($range, ',') !== false)
                {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                if ($range == '-')
                {
                    $c_start = $size - substr($range, 1);
                }
                else
                {
                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                }
                $c_end = ($c_end > $end) ? $end : $c_end;
                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size)
                {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                $start = $c_start;
                $end = $c_end;
                $length = $end - $start + 1;
                fseek($fp, $start);
                header('HTTP/1.1 206 Partial Content');
            }
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: " . $length);
            $buffer = 1024 * 8;
            $s = 0;
            while (!feof($fp) && ($p = ftell($fp)) <= $end){
                if ($p + $buffer > $end){
                    $buffer = $end - $p + 1;
                }
                $s = $s + 1;
                //take a break start/my modification
                echo fread($fp, $buffer);
                if ($s >= 500){
                    ob_clean();
                    ob_flush();
                    flush();
                    break;
                    //take a break
                }
                else{
                    flush();
                }
            }
            fclose($fp);
            exit();
        }
    }
}
else {
    http_response_code(401);
    exit;
}
?>