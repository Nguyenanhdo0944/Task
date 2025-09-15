<?php
namespace App\Controllers;

use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class UserController
{
    private $twig;
    private $uploadPath;
    private $cropPath;

    public function __construct()
    {
        // Khởi tạo Eloquent
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

        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader);

        // Thiết lập đường dẫn upload với realpath để chuẩn hóa đường dẫn
        $baseDir = realpath(__DIR__ . '/../../');
        $this->uploadPath = $baseDir . '/uploads/avatar/';
        $this->cropPath = $baseDir . '/uploads/avatar_crop/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
        if (!file_exists($this->cropPath)) {
            mkdir($this->cropPath, 0755, true);
        }
    }

    public function handleRequest()
    {
        // Thêm
        if (isset($_POST['add'])) {
            $avatarName = $this->handleAvatarUpload(
                $_FILES['avatar'] ?? null, 
                $_POST['avatar_data'] ?? null
            );
            
            User::create([
                'username' => $_POST['username'],
                'email'    => $_POST['email'],
                'phone'    => $_POST['phone'],
                'birthday' => $_POST['birthday'],
                'password' => password_hash($_POST['password'], PASSWORD_BCRYPT),
                'is_admin' => $_POST['is_admin'] ?? 0,
                'avatar'   => $avatarName,
            ]);
            header("Location: index.php");
            exit;
        }

        // Sửa
        if (isset($_POST['edit'])) {
            $user = User::find($_POST['id']);
            if ($user) {
                $user->username = $_POST['username'];
                $user->email    = $_POST['email'];
                $user->phone    = $_POST['phone'];
                $user->birthday = $_POST['birthday'];
                
                if (!empty($_POST['password'])) {
                    $user->password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                }
                
                $user->is_admin = $_POST['is_admin'] ?? 0;
                
                // Xử lý avatar
                $newAvatar = $this->handleAvatarUpdate(
                    $user->avatar, 
                    $_FILES['avatar'] ?? null, 
                    $_POST['remove_avatar'] ?? false,
                    $_POST['avatar_data'] ?? null
                );
                
                // CHỈ CẬP NHẬT NẾU THÀNH CÔNG
                if ($newAvatar !== false) {
                    $user->avatar = $newAvatar;
                }
                
                $user->save();
            }
            header("Location: index.php");
            exit;
        }

        // Xóa
        if (isset($_GET['delete'])) {
            $user = User::find($_GET['delete']);
            if ($user) {
                // Xóa avatar nếu có
                if ($user->avatar) {
                    $this->deleteAvatar($user->avatar);
                }
                $user->delete();
            }
            header("Location: index.php");
            exit;
        }

        $search = $_GET['search'] ?? '';
        $usersQuery = User::query();
        if ($search) {
            $usersQuery->where('username', 'LIKE', "%$search%")
                       ->orWhere('email', 'LIKE', "%$search%")
                       ->orWhere('phone', 'LIKE', "%$search%");
        }
        $users = $usersQuery->get();

        echo $this->twig->render('index.twig', [
            'users'   => $users,
            'search'  => $search,
            'session' => $_SESSION
        ]);
    }

    /**
     * Xử lý upload avatar và tạo avatar crop
     */
    private function handleAvatarUpload($file, $avatarData = null)
    {
        // Ưu tiên sử dụng avatar crop từ client nếu có
        if (!empty($avatarData)) {
            $cropResult = $this->handleAvatarCropData($avatarData);
            if ($cropResult) {
                return $cropResult;
            }
        }
        
        // Fallback: xử lý upload file như bình thường
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Kiểm tra loại file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        // Kiểm tra kích thước file (tối đa 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        // Tạo tên file mới
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $extension;

        // Di chuyển file vào thư mục uploads
        if (move_uploaded_file($file['tmp_name'], $this->uploadPath . $fileName)) {
            // Tạo avatar crop - ĐẢM BẢO CẢ HAI ĐỀU THÀNH CÔNG
            $cropSuccess = $this->createAvatarCrop($this->uploadPath . $fileName, $fileName);
            
            if ($cropSuccess) {
                return $fileName; // Trả về file gốc, crop đã được tạo
            } else {
                // Nếu tạo crop thất bại, xóa file gốc và return null
                unlink($this->uploadPath . $fileName);
                return null;
            }
        }

        return null;
    }

    /**
     * Xử lý dữ liệu avatar crop từ client
     */
    private function handleAvatarCropData($avatarData)
    {
        // Kiểm tra xem có phải là data URL không
        if (preg_match('/^data:image\/(\w+);base64,/', $avatarData, $matches)) {
            $extension = $matches[1];
            $data = substr($avatarData, strpos($avatarData, ',') + 1);
            $data = base64_decode($data);
            
            if ($data !== false) {
                $fileName = uniqid() . '_' . time() . '_crop.' . $extension;
                $filePath = $this->cropPath . $fileName;
                
                // Lưu file
                if (file_put_contents($filePath, $data)) {
                    return $fileName;
                }
            }
        }
        
        return null;
    }

    /**
     * Tạo avatar crop 64x64 từ ảnh gốc - Resize ảnh về 64x64 và lấy phần giữa
     */
    private function createAvatarCrop($sourcePath, $originalFileName)
    {
        // Kiểm tra xem server có hỗ trợ GD library không
        if (!function_exists('imagecreatetruecolor')) {
            error_log("GD library not available");
            return false;
        }
        
        // Kiểm tra file nguồn có tồn tại không
        if (!file_exists($sourcePath)) {
            error_log("Source file not found: " . $sourcePath);
            return false;
        }
        
        // Lấy thông tin ảnh
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            error_log("Invalid image file: " . $sourcePath);
            return false;
        }
        
        // Tạo image resource từ ảnh gốc
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($sourcePath);
                break;
            default:
                error_log("Unsupported image type: " . $imageInfo[2]);
                return false;
        }
        
        if (!$image) {
            error_log("Failed to create image from source");
            return false;
        }
        
        // Lấy kích thước ảnh gốc
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // Tính tỷ lệ resize để ảnh vừa với khung 64x64 mà không bị vỡ
        $ratio = max(64 / $originalWidth, 64 / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        // Tạo ảnh mới với kích thước đã resize
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Xử lý transparency cho PNG và GIF
        if ($imageInfo[2] == IMAGETYPE_PNG || $imageInfo[2] == IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            // Tạo nền trắng cho ảnh JPEG
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);
        }
        
        // Resize ảnh với chất lượng tốt
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Tạo ảnh crop 64x64 từ ảnh đã resize
        $cropped = imagecreatetruecolor(64, 64);
        
        // Xử lý transparency cho PNG và GIF
        if ($imageInfo[2] == IMAGETYPE_PNG || $imageInfo[2] == IMAGETYPE_GIF) {
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);
            $transparent = imagecolorallocatealpha($cropped, 255, 255, 255, 127);
            imagefilledrectangle($cropped, 0, 0, 64, 64, $transparent);
        } else {
            // Tạo nền trắng cho ảnh JPEG
            $white = imagecolorallocate($cropped, 255, 255, 255);
            imagefill($cropped, 0, 0, $white);
        }
        
        // Tính toán vị trí crop (lấy phần giữa của ảnh đã resize)
        $x = (int)(($newWidth - 64) / 2);
        $y = (int)(($newHeight - 64) / 2);
        
        // Crop ảnh
        imagecopy($cropped, $resized, 0, 0, $x, $y, 64, 64);
        
        // Tạo tên file cho avatar crop
        $info = pathinfo($originalFileName);
        $cropFileName = $info['filename'] . '_crop.' . $info['extension'];
        $cropFilePath = $this->cropPath . $cropFileName;
        
        // Lưu ảnh vào thư mục avatar_crop
        $saveSuccess = false;
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $saveSuccess = imagejpeg($cropped, $cropFilePath, 90);
                break;
            case IMAGETYPE_PNG:
                $saveSuccess = imagepng($cropped, $cropFilePath, 9);
                break;
            case IMAGETYPE_GIF:
                $saveSuccess = imagegif($cropped, $cropFilePath);
                break;
            case IMAGETYPE_WEBP:
                $saveSuccess = imagewebp($cropped, $cropFilePath, 90);
                break;
        }
        
        // Giải phóng bộ nhớ
        imagedestroy($image);
        imagedestroy($resized);
        imagedestroy($cropped);
        
        if (!$saveSuccess) {
            error_log("Failed to save cropped image to: " . $cropFilePath);
            return false;
        }
        
        return true;
    }

    /**
     * Xử lý cập nhật avatar
     */
    private function handleAvatarUpdate($currentAvatar, $newFile, $removeAvatar = false, $avatarData = null)
    {
        // Nếu chọn xóa avatar
        if ($removeAvatar) {
            if ($currentAvatar) {
                $this->deleteAvatar($currentAvatar);
            }
            return null;
        }

        // Nếu có upload avatar mới hoặc có dữ liệu crop từ client
        if (($newFile && $newFile['error'] === UPLOAD_ERR_OK) || !empty($avatarData)) {
            // Xóa avatar cũ nếu có
            if ($currentAvatar) {
                $this->deleteAvatar($currentAvatar);
            }
            
            // Upload avatar mới và kiểm tra kết quả
            $newAvatar = $this->handleAvatarUpload($newFile, $avatarData);
            return $newAvatar !== null ? $newAvatar : $currentAvatar;
        }

        // Giữ nguyên avatar hiện tại
        return $currentAvatar;
    }

    /**
     * Xóa avatar
     */
    private function deleteAvatar($avatarName)
    {
        if (!$avatarName) return;
        
        // Xóa avatar gốc
        $originalPath = $this->uploadPath . $avatarName;
        if (file_exists($originalPath)) {
            @unlink($originalPath);
        }
        
        // Xóa avatar crop
        $info = pathinfo($avatarName);
        $cropFileName = $info['filename'];
        
        // Nếu là file crop từ client, có hậu tố _crop
        if (strpos($cropFileName, '_crop') === false) {
            $cropFileName .= '_crop';
        }
        
        $cropFileName .= '.' . $info['extension'];
        $cropPath = $this->cropPath . $cropFileName;
        
        if (file_exists($cropPath)) {
            @unlink($cropPath);
        }
    }

    /**
     * Lấy đường dẫn avatar
     */
    public static function getAvatarPath($avatarName, $isCrop = false)
    {
        if (!$avatarName) {
            return self::getDefaultAvatar();
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
            return self::getDefaultAvatar();
        }
        
        return "/uploads/" . ($isCrop ? 'avatar_crop/' : 'avatar/') . $avatarName;
    }

    /**
     * Avatar mặc định
     */
    private static function getDefaultAvatar()
    {
        return "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Y2lyY2xlIGN4PSIzMiIgY3k9IjMyIiByPSIzMiIgZmlsbD0iI0Q1RDVENTQiLz4KICA8dGV4dCB4PSIzMiIgeT0iMzUiIGZvbnQtc2l6ZT0iMjAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM3OTc5NzkiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiI+QUk8L3RleHQ+Cjwvc3ZnPg==";
    }

    /**
     * Phương thức sửa avatar lỗi
     */
    public function fixBrokenAvatars()
    {
        $users = User::whereNotNull('avatar')->get();
        
        foreach ($users as $user) {
            $avatarPath = $this->uploadPath . $user->avatar;
            $cropPath = $this->cropPath . pathinfo($user->avatar, PATHINFO_FILENAME) . '_crop.' . pathinfo($user->avatar, PATHINFO_EXTENSION);
            
            // Nếu có file gốc nhưng không có crop
            if (file_exists($avatarPath) && !file_exists($cropPath)) {
                if ($this->createAvatarCrop($avatarPath, $user->avatar)) {
                    echo "Đã tạo crop cho: " . $user->username . "<br>";
                }
            }
            // Nếu không có cả hai
            elseif (!file_exists($avatarPath) && !file_exists($cropPath)) {
                $user->avatar = null;
                $user->save();
                echo "Đã xóa avatar lỗi cho: " . $user->username . "<br>";
            }
        }
    }
}