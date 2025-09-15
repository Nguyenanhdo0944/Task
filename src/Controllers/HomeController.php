<?php
namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\User;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class HomeController
{
    private $twig;

    public function __construct()
    {
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

        if (empty($_SESSION['user_id'])) {
            header("Location: login.php");
            exit;
        }
    }

    public function handleRequest()
    {
        $user = User::find($_SESSION['user_id']);

        // Tính toán avatar path trước
        $avatarCropPath = $this->getAvatarPath($user->avatar, true);
        $avatarOriginalPath = $this->getAvatarPath($user->avatar, false);

        // Render Twig
        echo $this->twig->render('home.twig', [
            'user'    => $user,
            'session' => $_SESSION,
            'avatarCropPath' => $avatarCropPath,
            'avatarOriginalPath' => $avatarOriginalPath
        ]);
    }

    /**
     * Lấy đường dẫn avatar
     */
    private function getAvatarPath($avatarName, $isCrop = false)
    {
        if (!$avatarName) {
            return $this->getDefaultAvatar();
        }
        
        $baseDir = realpath(__DIR__ . '/../../');
        $filePath = $baseDir . '/uploads/' . ($isCrop ? 'avatar_crop/' : 'avatar/') . $avatarName;
        
        // Nếu file không tồn tại, kiểm tra file thay thế
        if (!file_exists($filePath)) {
            // Nếu đang yêu cầu crop nhưng không có, thử trả về file gốc
            if ($isCrop) {
                $originalPath = $baseDir . '/uploads/avatar/' . $avatarName;
                if (file_exists($originalPath)) {
                    return "/uploads/avatar/" . $avatarName;
                }
            }
            return $this->getDefaultAvatar();
        }
        
        return "/uploads/" . ($isCrop ? 'avatar_crop/' : 'avatar/') . $avatarName;
    }

    /**
     * Avatar mặc định
     */
    private function getDefaultAvatar()
    {
        return "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Y2lyY2xlIGN4PSIzMiIgY3k9IjMyIiByPSIzMiIgZmlsbD0iI0Q1RDVENTQiLz4KICA8dGV4dCB4PSIzMiIgeT0iMzUiIGZvbnQtc2l6ZT0iMjAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM3OTc5NzkiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiI+QUk8L3RleHQ+Cjwvc3ZnPg==";
    }
}