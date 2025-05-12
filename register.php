<?php

    session_start();
    
    include('./db_connect.php');

    function check_account_email($conn , $username, $email , &$error) {
        $checkSql = "SELECT user_name , account FROM users WHERE user_name = ? OR account = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $accountExists = false;
        $emailExists = false;
        
        while($row = $result->fetch_assoc()) {
            if ($row['user_name'] === $username) {
                $accountExists = true;
            }
            if ($row['account'] === $email) {
                $emailExists = true;
            }
        }
        $stmt->close();

        if ($accountExists) {
            echo "<script>alert('Username existed!'); </script>";
            $error += 1;
        } 
        if ($emailExists) {
            echo "<script>alert('Email existed!'); </script>";
            $error += 1;
        } 
    }

    function handle_file($file , &$error) {
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpg','image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
    
    
            // check file type
            if (!in_array($fileType, $allowedTypes)) {
                echo "<script>alert('Unsupported file type! Please upload a JPG, JPEG, PNG, or GIF file.'); </script>";
                $error += 1;
                return '';
            }

            // upload file
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time().'-'.basename($file['name']);
            $uploadFilePath = $uploadDir . $fileName;
            move_uploaded_file($file['tmp_name'], $uploadFilePath);

            return $uploadFilePath;
        } else {
            $error += 1;
            $errorMessage = file_upload_error_message($file['error']);
            echo "<script>alert('File upload error! " . $errorMessage . "'); </script>";
        }
        return '';
    }

    function file_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_OK:
                return 'There is no error, the file uploaded successfully.';
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded files exceeds the post_max_size directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }
    
    function register_user($conn, $user_data) {
        $sql = "INSERT INTO users (user_name, account, hash_password, user_picture, fullname, address, phone)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssss",
            $user_data['username'], 
            $user_data['email'], 
            $user_data['hash_password'], 
            $user_data['user_picture'], 
            $user_data['fullname'], 
            $user_data['address'], 
            $user_data['phone']
        );
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    $error = 0;
    // 取得表單資料，並用 trim() 去除使用者輸入前後可能多餘的空白
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'];
        $confirm = $_POST['confirm'];
        $fullname = trim($_POST['fullname'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        // if (isset($_FILES['picture'])) {
        //     echo "<script>alert('File exist!'); </script>";
        // }
        

        // 檢查密碼是否一致
        if ($password !== $confirm) {
            echo "<script>alert('The two passwords do not match! Please check again'); </script>";
            $error += 1;
        } 
        check_account_email($conn , $username, $email, $error);
        $user_picture = handle_file($_FILES['picture'], $error);

        // 加密密碼
        if ($error == 0) {
            $hash_password = password_hash($password, PASSWORD_DEFAULT);
            $user_data = [
                'username' => $username,
                'email' => $email,
                'hash_password' => $hash_password,
                'user_picture' => $user_picture,
                'fullname' => $fullname,
                'address' => $address,
                'phone' => $phone
            ];
            $success = register_user($conn, $user_data);

            if ($success) {
                $conn->close();
                echo "<script>alert('Registration successful!'); window.location.href = 'login.html';</script>";
                exit();
            } else {
                echo "<script>alert('Registration failed! Please try again.'); window.location.href = 'register.php';</script>";
            }
        }
        else {
            echo "<script> window.location.href = 'register.php';</script>";
        }
        $conn->close();
    }    
?>

<!DOCTYPE html>

<html lang="en">

    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="./register.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" 
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" 
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    </head>

    <body class="register-page">
        <div class="register-container">
            <h2>Register</h2>
            <br>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="reg-user">
                    <label>Input user name</label><br>
                    <input type="text" name="username" placeholder="USER NAME" required>
                </div>
                <div class="reg-email">
                    <label>Input your email (Account)</label><br>
                    <input type="email" name="email" placeholder="EMAIL(ACCOUNT)" required>
                </div>
                <div class="reg-password">
                    <label>Input your password</label><br>
                    <input type="password" name="password" id="password" placeholder="PASSWORD" required>
                    <span class="toggle-pw"><i class="fa-solid fa-eye-slash"></i></span>
                </div>
                <div class="reg-confirm">
                    <label>Confirm your password</label><br>
                    <input type="password" name="confirm" id="confirm" placeholder="PASSWORD" required>
                    <span class="toggle-cf"><i class="fa-solid fa-eye-slash"></i></span>
                </div>
                <div class="reg-photo">
                    <label>Upload your user photo within 2MB</label><br>
                    <div id="preview"></div>
                    <input type="file" name="picture" id="upload" accept="image/jpeg,image/jpg,image/png,image/gif" required>
                </div>
                <div class="reg-fullname">
                    <label>Input your fullname</label><br>
                    <input type="text" name="fullname" placeholder="FULLNAME" required>
                </div>
                <div class="reg-address">
                    <label>Input your address</label><br>
                    <input type="text" name="address" placeholder="ADDRESS" required>
                </div>
                <div class="reg-phone">
                    <label>Input your phone number</label><br>
                    <input type="text" name="phone" placeholder="PHONE NUMBER" required>
                </div>

                <button type="submit" class="register-button">CREATE ACCOUNT</button>
            </form>

        </div>
        <script src="./register.js"></script>
    </body>
</html>