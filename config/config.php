<?php
ob_start();//output buffring 
error_reporting(E_ALL);//all php error
error_reporting(1);//only fatal error

$rootPath = $_SERVER['DOCUMENT_ROOT'];
if($_SERVER['SERVER_NAME'] != 'localhost')
{
    defined('MODE') ? null : define('MODE','LIVE');
}
else
{
    defined('MODE') ? null : define('MODE','LOCAL');
}
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
// echo MODE;exit;
if(MODE == 'LOCAL')
{
    $ex=explode('\\',getcwd());
    defined('DR') ? null : define('DR',str_replace('/','\\',$_SERVER['DOCUMENT_ROOT']).DS.$ex[3]);
}
else
{
    $ex=explode('\\',getcwd());
    // defined('DR') ? null : define('DR',str_replace('/','\\',$_SERVER['DOCUMENT_ROOT']));
    // echo DR.DS;exit;
    defined('DR') ? null : define('DR',str_replace('/','\\',$_SERVER['DOCUMENT_ROOT']).DS.$ex[3]);
}

if(MODE == 'LOCAL')
{
    $site_url="http://localhost/employee/";
    $image_url="http://localhost/employee/src/images";
    defined('servername') ? null : define('servername','localhost');
    defined('username') ? null : define('username','root');
    defined('password') ? null : define('password','');
    defined('database') ? null : define('database','employee');
}
else
{
    $site_url="http://localhost/employee/";
    $image_url="http://localhost/employee/src/images";
    defined('servername') ? null : define('servername','localhost');
    defined('username') ? null : define('username','root');
    defined('password') ? null : define('password','Babutk@18');
    defined('database') ? null : define('database','tk_traders');

    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
        $httpsUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $httpsUrl", true, 301);
        exit();
    }
}

$configdata=array(
    "site"=>"Capminds",
    "brand_name"=>"capminds technologies",
    "logo"=>$site_url."storage/images/logo.png",
    "cmobile"=>"",
    "email"=>"",
    "location"=>"T. Nagar,Chennai",
    "pincode"=>"600017",
    "address"=>"7/3, 2nd Floor, Madley Road, T. Nagar, Chennai - 600017.",
    "smtp"=> [
        'host' => 'email-smtp.ap-south-1.amazonaws.com',
        'port' => 587,
        'username' => 'AKIASKWYHLXUEN53E35C',
        'password' => 'BEf90DeIh7CihqiIc6+/c/NTfGlnDircbrT9OHNFoMVY',
        'from_email' => 'tkgokul455@gmail.com',
        'from_name' => 'Capminds'
    ]
);




class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . servername . ";dbname=" . database . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, username, password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]);
        } catch (PDOException $e) {
            die(" Connection Failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}

class curd {

    private static function getPdoType($value) {
        if (is_int($value)) return PDO::PARAM_INT;
        if (is_bool($value)) return PDO::PARAM_BOOL;
        if (is_null($value)) return PDO::PARAM_NULL;
        return PDO::PARAM_STR;
    }

    public static function select($sql, $params = []) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, self::getPdoType($value));
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }
    public static function insert($sql, $params = []) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, self::getPdoType($value));
        }

        $stmt->execute();
        return $db->lastInsertId();
    }

    public static function update($sql, $params = []) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, self::getPdoType($value));
        }

        return $stmt->execute();
    }

    public static function delete($sql, $params = []) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, self::getPdoType($value));
        }

        return $stmt->execute();
    }

}


require_once DR . DS.'libs'.DS.'fpdf'.DS.'fpdf.php';
require_once DR . DS. 'libs'.DS.'phpmailer'.DS.'PHPMailer.php';
require_once DR . DS. 'libs'.DS.'phpmailer'.DS.'SMTP.php';
require_once DR . DS. 'libs'.DS.'phpmailer'.DS.'Exception.php';

// use PHPUnit\Framework\TestCase;