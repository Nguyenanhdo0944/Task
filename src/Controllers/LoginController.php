<?php
namespace App\Controllers;

use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class LoginController
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
            'password'  => "",
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader);

        session_start();
    }

    public function handleRequest()
    {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            $user = User::where('email', $email)->first();

            if ($user && password_verify($password, $user->password)) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $_SESSION['is_admin'] = $user->is_admin;

                if ($user->is_admin == 1) {
                    header("Location: index.php"); 
                } else {
                    header("Location: home.php"); 
                }
                exit;
            } else {
                $error = "Email hoặc mật khẩu không đúng!";
            }
        }

        // Render Twig
        echo $this->twig->render('login.twig', [
            'error' => $error,
            'session' => $_SESSION
        ]);
    }
}
