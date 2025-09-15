<?php
session_start();

// Hủy toàn bộ session
session_unset();
session_destroy();

// Chuyển về trang đăng nhập
header("Location: login");
exit;
