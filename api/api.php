<?php require "../config/config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class EmployeeService
{
    /*  LIST  */
    public static function list(): array
    {
        $sql = "SELECT id, name, image_path, created_at
                FROM employees
                ORDER BY created_at DESC";

        return curd::select($sql);
    }

    /*  CREATE  */
    public static function create(array $post, array $files): int
    {
        $name = trim($post['name'] ?? '');
        // echo $name;exit;
        if ($name === '') {
            throw new RuntimeException('Name required');
        }

        $imagePath = null;
        // echo $files['image'];exit;
        if (!empty($files['image']) && $files['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagePath = FileStorage::storeImage($files['image']);
        }

        $sql = "INSERT INTO employees (name, image_path)
                VALUES (:name, :image)";

        $id = curd::insert($sql, [
            'name'  => $name,
            'image' => $imagePath
        ]);

        Logger::log('info', 'Employee created', ['id' => $id]);
        self::sendEmail($id, $name);

        return $id;
    }

    /*  UPDATE  */
    public static function update(array $post, array $files): bool
    {
        $id   = (int)($post['id'] ?? 0);
        // echo $id;exit;
        $name = trim($post['name'] ?? '');

        if (!$id || $name === '') {
            throw new RuntimeException('Invalid input');
        }

        $row = self::getById($id);
        if (!$row) {
            throw new RuntimeException('Employee not found');
        }

        $imagePath = $row['image_path'];
        if (!empty($files['image']) && $files['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagePath = FileStorage::storeImage($files['image']);
        }

        $sql = "UPDATE employees
                SET name = :name, image_path = :image
                WHERE id = :id";

        $ok = curd::update($sql, [
            'name'  => $name,
            'image' => $imagePath,
            'id'    => $id
        ]);

        Logger::log('info', 'Employee updated', ['id' => $id]);
        return $ok;
    }

    /*  DELETE  */
    public static function delete(array $post): bool
    {
        $id = (int)($post['id'] ?? 0);
        if (!$id) {
            throw new RuntimeException('Invalid ID');
        }

        $sql = "DELETE FROM employees WHERE id = :id";
        $ok  = curd::delete($sql, ['id' => $id]);

        Logger::log('info', 'Employee deleted', ['id' => $id]);
        return $ok;
    }

    /*  GET BY ID  */
    private static function getById(int $id): ?array
    {
        $sql = "SELECT id, name, image_path, created_at
                FROM employees
                WHERE id = :id";

        $row = curd::select($sql, ['id' => $id]);
        return $row[0] ?? null;
    }

    /*  EMAIL  */
    
    private static function sendEmail(int $id, string $name): void
    {
        global $configdata;
        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host       = $configdata['smtp']['host'];
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $configdata['smtp']['username'];
            $mailer->Password   = $configdata['smtp']['password'];
            $mailer->Port       = $configdata['smtp']['port'];

            $mailer->setFrom($configdata['smtp']['from_email'], $configdata['smtp']['from_name']);
            $mailer->addAddress($configdata['smtp']['from_email']);

            $mailer->Subject = "New employee created: $name";
            $mailer->Body    = "Employee ID: $id\nName: $name";

            $mailer->send();
        } catch (Throwable $e) {
            Logger::log('warning', 'Mailer failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    public static function employeePdf(int $id)
    {
        $emp = self::getById($id);

        if (!$emp) {
            throw new RuntimeException('Employee not found');
        }
        $pdf = new FPDF();
        $pdf->AddPage();
                
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY(0, 12);
        $pdf->Cell(0, 8, 'CAPMINDS TECHNOLOGIES', 0, 1, 'R');

        // line
        $pdf->Line(10, 35, 200, 35);

       

        $pdf->SetY(45);

        // Title
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Employee Details', 0, 1);

        // Employee data
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 8, 'id:', 0, 0);
        $pdf->Cell(0, 8, $emp['id'], 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 8, 'Name:', 0, 0);
        $pdf->Cell(0, 8, $emp['name'], 0, 1);

        $pdf->Cell(40, 8, 'Created At:', 0, 0);
        $pdf->Cell(0, 8,  $emp['created_at'], 0, 1);

        $pdf->Ln(8);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, 'Company Address', 0, 1);

        $pdf->SetFont('Arial', '', 11);
        $pdf->MultiCell(0, 6,
            "Capminds Technologies\n" .
            "7/3, 2nd Floor, Madley Road\n" .
            "T. Nagar, Chennai - 600017\n" .
            "Location: T. Nagar, Chennai\n" .
            "Pincode: 600017"
        );

        // Bottom line
        $pdf->Line(10, $pdf->GetY() + 3, 200, $pdf->GetY() + 3);

    //    page 2
        if (!empty($emp['image_path'])) {
            $pdf->AddPage();

            // Photo title
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, 'Employee Photo', 0, 1, 'C');
            $pdf->Ln(10);

        
            $photo = DR.DS . $emp['image_path']; // static image
            if (file_exists($photo)) {
                $imgWidth = 90;
                $x = ($pdf->GetPageWidth() - $imgWidth) / 2;
                $pdf->Image($photo, $x, $pdf->GetY(), $imgWidth);
            }
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename=employee_static.pdf');
        $pdf->Output('I');
        exit;
           
    }
}

class Logger {
    public static function log(string $level, string $message, array $context = []): void {

        $logDir = DR . DS .  'logs';

        // logs directory exists
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/app.log';

        $entry = sprintf(
            "[%s] %s: %s %s\n",
            date('c'),
            strtoupper($level),
            $message,
            json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}

class FileStorage
{
    public static function storeImage(array $file): string
    {
        self::validate($file);
        self::ensureDir();

        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
        $name = bin2hex(random_bytes(8)) . '.' . $ext;

        $fullPath = rtrim(DR.DS.'src/images', '/') . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new RuntimeException('Upload failed');
        }

        self::thumbnail($fullPath);

        return 'src/images/' . $name;
    }

    /*  HELPERS  */

    private static function ensureDir(): void
    {
        if (!is_dir(DR.DS.'src/images')) {
            mkdir(DR.DS.'src/images', 0750, true);
        }
    }

    private static function validate(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error');
        }

        if ($file['size'] > 2 * 1024 * 1024) {//2mb
            throw new RuntimeException('File too large');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            throw new RuntimeException('Invalid file type');
        }
    }

    private static function thumbnail(string $path): void
    {
        if (!extension_loaded('gd')) return;

        $info = getimagesize($path);
        if (!$info) return;

        [$w, $h] = $info;
        $thumbW = 200;
        $thumbH = 200;

        switch ($info['mime']) {
            case 'image/jpeg': $src = imagecreatefromjpeg($path); break;
            case 'image/png':  $src = imagecreatefrompng($path);  break;
            case 'image/gif':  $src = imagecreatefromgif($path);  break;
            default: return;
        }

        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagecopyresampled($thumb, $src, 0,0,0,0, $thumbW, $thumbH, $w, $h);

        imagejpeg($thumb, dirname($path).'/thumb_'.basename($path), 85);
        imagedestroy($src);
        imagedestroy($thumb);
    }
}

// final class EmployeeServiceTest extends TestCase
// {
//     public static function setUpBeforeClass(): void
//     {
//         if (!class_exists('PHPMailer')) {
//             class PHPMailer {
//                 public function __construct($e=false){}
//                 public function isSMTP(){}
//                 public function setFrom(){}
//                 public function addAddress(){}
//                 public function send(){ return true; }
//                 public function __set($n,$v){}
//             }
//         }
//     }

//     public function testCreateGetDeleteEmployee(): void
//     {
//         /* ---------- CREATE ---------- */
//         $id = EmployeeService::create(
//             ['name' => 'Test User'],
//             [] // no image
//         );

//         $this->assertIsNumeric($id, 'Create should return inserted ID');

       
//         $ref = new ReflectionClass(EmployeeService::class);
//         $method = $ref->getMethod('getById');
//         $method->setAccessible(true);

//         $row = $method->invoke(null, $id);

//         $this->assertNotEmpty($row);
//         $this->assertEquals('Test User', $row['name']);

        
//         $deleted = EmployeeService::delete(['id' => $id]);
//         $this->assertTrue($deleted);
//     }
// }

?>