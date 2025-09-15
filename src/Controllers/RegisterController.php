<?php
namespace App\Controllers;

use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class RegisterController
{
    private $twig;

    public function __construct()
    {
        // Khởi tạo DB Eloquent
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'retask0',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Khởi tạo Twig
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader);

        session_start();
    }

    public function handleRequest()
    {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if (User::where('email', $email)->exists()) {
                $error = "Email đã tồn tại!";
            } else {
                User::create([
                    'username' => $username,
                    'email'    => $email,
                    'phone'    => $_POST['phone'] ?? null,
                    'birthday' => $_POST['birthday'] ?? null,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'is_admin' => 0,
                ]);
                $_SESSION['message'] = "Đăng ký thành công, mời đăng nhập!";
                header("Location: login.php");
                exit;
            }
        }

        echo $this->twig->render('register.twig', [
            'error' => $error,
            'session' => $_SESSION
        ]);
    }
}
