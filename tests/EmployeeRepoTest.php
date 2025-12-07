<?php
use PHPUnit\Framework\TestCase;
use App\Database;
use App\EmployeeRepo;


final class EmployeeRepoTest extends TestCase {
public static function setUpBeforeClass(): void {
// configure test DB in Config.php or env
}


public function testCreateAndGet(): void {
$id = EmployeeRepo::create('Test User', null);
$this->assertIsInt($id);
$row = EmployeeRepo::getById($id);
$this->assertEquals('Test User', $row['name']);
EmployeeRepo::delete($id);
}
}