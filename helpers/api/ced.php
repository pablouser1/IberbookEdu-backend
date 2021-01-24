<?php
require_once(__DIR__."/../../classes/groups.php");
require_once(__DIR__."/../../classes/schools.php");
require_once(__DIR__. "/../../config/config.php");
require_once(__DIR__. "/requests.php");
class Api {
    // -- Initial vars -- //
    private $req;
    private $db;
    private $base_url;
    private $type;
    private $cookies;

    // -- Base functions -- //
    function __construct() {
        // Class from requests.php
        $this->req = new req();
        $this->db = new DB();
    }
    // https://stackoverflow.com/a/10514539, eliminates duplicates in array
    function super_unique($array,$key)
    {
        $temp_array = [];
        foreach ($array as &$v) {
            if (!isset($temp_array[$v[$key]]))
            $temp_array[$v[$key]] =& $v;
        }
        $array = array_values($temp_array);
        return $array;
    }

    // -- Common functions -- //
    function settype($type) {
        $this->type = $type;
        switch ($type) {
            case "students":
            case "guardians":
                $this->base_url = $GLOBALS["base_url"].'pasendroid';
            break;
            case "teachers":
                $this->base_url = $GLOBALS["base_url"].'senecadroid';
            break;
            default:
                die("Invalid user type");
        }
    }

    // Login user
    function login($username, $password, $type){
        // Initial config
        $useragent = "IberbookEdu Testing";
        $this->settype($type);
        $data = array('p' => '{"version":"11.10.5"}', 'USUARIO' => $username, 'CLAVE' => $password);
        // Options
        $url = "{$this->base_url}/login";
        $initial_options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => true,
            CURLOPT_USERAGENT => $useragent,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5
        );
        $options = $initial_options + $GLOBALS["ssloptions"];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        // Get headers and JSON data
        $response = curl_exec($ch);
        // Check if any errors (timeouts...)
        if(curl_error($ch))
        {
            return array(
                "code" => "CURL",
                "error" => L::ced_remoteServer
            );
        }
        $json_data = mb_substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));  
        $result = json_decode($json_data, true);
        curl_close($ch);
        if ($result["ESTADO"]["CODIGO"] != "C"){
            return array(
                "code" => $result["ESTADO"]["CODIGO"],
                "error" => $result["ESTADO"]["DESCRIPCION"]
            );
        }
        // Get cookies
        $cookies = "";
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        foreach($matches[1] as $item) {
            $cookies .= "{$item};";
        }
        $this->req->setcookies($cookies);

        return [
            "code" => $result["ESTADO"]["CODIGO"],
            "error" => null
        ];
    }
    
    // Get basic data from user
    function getinfo(){
        $userinfo = false;
        $url = "{$this->base_url}/infoSesion";
        $info = $this->req->get($url);
        // Save common user info to array
        switch($this->type){
            // -- Alumno -- //
            case 'students':
                // Check if user is actually student
                if ($this->type == "students" && $info["RESULTADO"][0]["C_PERFIL"] == "ALU") {
                    // Get id from local database
                    $idced = $info["RESULTADO"][0]["MATRICULAS"][0]["X_MATRICULA"];
                    $id = $this->doesUserExists($idced);
                    // Get school id and name
                    $datacentro = array("X_CENTRO" => $info["RESULTADO"][0]["MATRICULAS"][0]["X_CENTRO"]);
                    $infocentro = $this->getcentrostudent($datacentro);
                    $schoolid = $infocentro["schoolid"];
                    $group = [
                        "name" => $info["RESULTADO"][0]["MATRICULAS"][0]["UNIDAD"]
                    ];
                    if ($this->isAllowed($schoolid, $group["name"])) {
                        // Set user info
                        $userinfo = [
                            "id" => $id,
                            "name" => $info["RESULTADO"][0]["USUARIO"],
                            "type" => "students",
                            "schoolid" => $schoolid,
                            "schoolname" => $infocentro["schoolname"],
                            "year" => $group["name"],
                            "schools" => [
                                [
                                    "id" => $schoolid,
                                    "name" => $infocentro["schoolname"],
                                    "groups" => [$group]
                                ]
                            ]
                        ];
                    }
                }
                break;
            // -- Tutor legal -- //
            case 'guardians':
                // Check if user is actually guardian
                if ($this->type == "guardians" && $info["RESULTADO"][0]["C_PERFIL"] == "TUT_LEGAL") {
                    // Set user info
                    $children = array();
                    foreach($info["RESULTADO"][0]["HIJOS"] as $tempchild){
                        // Check if student is allowed
                        $datacentro = array("X_CENTRO" => $tempchild["MATRICULAS"][0]["X_CENTRO"]);
                        $infocentro = $this->getcentrostudent($datacentro);
                        // If student is allowed, include him in array
                        $schoolid = $infocentro["schoolid"];
                        $group =  [
                            "name" => $tempchild["MATRICULAS"][0]["UNIDAD"]
                        ];
                        $idced = $tempchild["MATRICULAS"][0]["X_MATRICULA"];
                        $id = $this->doesUserExists($idced);
                        if ($this->isAllowed($schoolid, $group["name"])) {
                            // Merge child info with school info
                            $children[] = [
                                "id" => $id,
                                "name" => $tempchild["NOMBRE"],
                                "type" => "students",
                                "schools" => [
                                    [
                                        "id" => $schoolid,
                                        "name" => $infocentro["schoolname"],
                                        "groups" => [$group]
                                    ]
                                ]
                            ];
                        }
                    }
                    $userinfo = [
                        "name" => $info["RESULTADO"][0]["USUARIO"],
                        "type" => "guardians",
                        "child" => $children[0], // Hijo seleccionado por defecto
                        "children" => $children
                    ];
                }
                break;
            // -- Profesor -- //
            case 'teachers':
                $idced = $this->getidteacher();
                // Only one school
                if (!isset($info["RESULTADO"][0]["CENTROS"])) {
                    $schoolid = $info["RESULTADO"][0]["C_CODIGO"];
                    $school = $this->getallteacher($info["RESULTADO"][0]);
                    if ($school) {
                        $finalschools[] = $school;
                    }
                    else {
                        $finalschools = [];
                    }
                }
                // Multiple schools
                else {
                    $finalschools = [];
                    foreach($info["RESULTADO"][0]["CENTROS"] as $centro) {
                        $schoolid = $centro["C_CODIGO"];
                        $data = ["X_CENTRO" => $centro["X_CENTRO"], "C_PERFIL" => "P"];
                        // Get school info
                        $this->changeschoolteachers($data);
                        $school = $this->getallteacher($centro);
                        if ($school) {
                            $finalschools[] = $school;
                        }
                    }
                }

                $id = $this->doesUserExists($idced);
                $userinfo = array(
                    "id" => $id,
                    "name" => $info["RESULTADO"][0]["USUARIO"],
                    "type" => "teachers",
                    "subject" => $finalschools[0]["groups"][0]["subject"],
                    "schools" => $finalschools
                );
            break;
        }
        return $userinfo;
    }

    function isAllowed($schoolid, $group) {
        $groupname = $group["name"];
        $schools = new Schools;
        $groups = new Groups;
        $allowedSchool = $schools->isAllowed($schoolid);
        $allowedGroup = $groups->isAllowed($groupname);
        if ($allowedSchool && $allowedGroup) {
            return true;
        }
        return false;
    }

    // -- Students only -- //
    // Get school id
    function getcentrostudent($data){
        $url = "{$this->base_url}/datosCentro";
        $response = json_decode(utf8_encode($this->req->post($url, $data)), true);
        return array(
            "schoolid" => $response["RESULTADO"][0]["DATOS"][0][1],
            "schoolname" => $response["RESULTADO"][0]["DATOS"][2][1]
        );
    }

    // -- Teachers only -- //
    // Get id of teacher
    function getidteacher(){
        $url = "{$this->base_url}/getDatosUsuario";
        $response = json_decode(utf8_encode($this->req->post($url, [])), true);
        return $response["RESULTADO"][0]["DATOS"][0]["C_NUMIDE"]; // Teacher's id
    }
    
    function getallteacher($centro) {
        $schoolinfo = [];
        // Set array with only allowed schools
        $stmt = $this->db->prepare("SELECT id FROM schools WHERE id=?");
        $stmt->bind_param("i", $centro["C_CODIGO"]);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows == 1) {
            $groups = $this->getgroupsteachers();
            while ($stmt->fetch()) {
                // Set basic school info
                $schoolinfo = [
                    "name" => $centro["CENTRO"],
                    "id" => $centro["C_CODIGO"],
                ];
            }
            $stmt->close();
            // Set groups info
            if(empty($groups)){
                return null;
            }
            else {
                $schoolinfo["groups"] = $groups;
            }
        }
        return $schoolinfo;
    }

    // Change between schools (if needed)
    function changeschoolteachers($data){
        $url = "{$this->base_url}/setCentro";
        $response = json_decode(utf8_encode($this->req->post($url, $data)), true);
        if ($response["ESTADO"]["CODIGO"] == "C") return true;
        else return false;
    }

    function getgroupsteachers(){
        $url = "{$this->base_url}/getGrupos";
        $response = json_decode(utf8_encode($this->req->post($url, [])), true);
        // Get each course, split all groups and if there are any 4º ESO, 2º BCT, 6 Primaria add it to array
        foreach($response["RESULTADO"] as $id => $grupo){
            // TODO, integrate with DB
            preg_match_all("/(4º\sESO)\s.|(2º\sBCT)\s.|(6.)P/", $grupo["UNIDADES"], $tempgrupo);
            foreach ($tempgrupo[0] as $temp) {
                $grupos_repeated[] = [
                    "name" => $temp,
                    "subject" => $grupo["MATERIAS"]
                ];
            }
        }

        if(isset($grupos_repeated)) {
            // Sort
            $grupos = $this->super_unique($grupos_repeated, "name");
            return array_values($grupos);
        }
        else {
            return [];
        }
    }

    public function doesUserExists($userinfo) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE idced=? AND schoolyear=? AND schoolid=?");
        $stmt->bind_param("ssi", $userinfo["idced"], $userinfo["year"], $userinfo["schoolid"]);
        $stmt->execute();
        $stmt->store_result();
        // Get profile id
        $stmt->bind_result($idprofile);
        $stmt->fetch();
        $exists = $stmt->num_rows;
        $stmt->close();
        if ($exists === 0) {
            return $this->createUser($userinfo);
        }
        else {
            return $idprofile;
        }
    }

    private function createUser($userinfo) {
        $subject = null;
        if (isset($userinfo["subject"])) {
            $subject = $userinfo["subject"];
        }
        // Create user
        $stmt = $this->db->prepare("INSERT INTO `users` (`idced`, `type`, `fullname`, `subject`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $userinfo["idced"], $userinfo["type"], $userinfo["name"], $userinfo["schoolid"], $userinfo["year"], $subject);
        $stmt->execute();
        $userid = $stmt->insert_id;
        return $userid;
    }
}
?>
