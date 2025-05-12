
<?php
    session_start(); // 啟動 session，用於儲存使用者的登入狀態和購物車資訊
    include('./db_connect.php'); // 引入資料庫連線檔案

    // 定義產品類別陣列
    $categories = [
        "All Categories", // 所有類別選項
        "Electronics & Accessories", // 電子產品與配件
        "Home Appliances & Living Essentials", // 家電與生活必需品
        "Clothing & Accessories", // 服裝與配件
        "Beauty & Personal Care", // 美容與個人護理
        "Food & Beverages", // 食品與飲料
        "Home & Furniture", // 家居與傢具
        "Sports & Outdoor Equipment", // 運動與戶外設備
        "Automotive & Motorcycle Accessories", // 汽車與摩托車配件
        "Baby & Maternity Products", // 嬰兒與孕婦產品
        "Books & Office Supplies", // 書籍與辦公用品
        "Other" // 其他類別
    ];

    // 處理搜尋和類別過濾
    $search = isset($_GET['search']) ? $_GET['search'] : ''; // 有keyword時 keyword=search else keyword=''
    $category = isset($_GET['category']) ? $_GET['category'] : 'All Categories'; // 從 URL 參數獲取類別，預設為 "All Categories"
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // page預設為第 1 頁 else page=page
    $products_per_page = 8; // 每頁顯示 8 個產品 (4x2 網格)
    $offset = ($page - 1) * $products_per_page; // 計算 SQL OFFSET 值，用於分頁查詢

    // 構建 SQL 查詢
    $sql = "SELECT products.*, users.user_name AS seller_name FROM products 
            JOIN users ON products.seller_id = users.user_id 
            WHERE 1=1"; // 從 products 和 users 表中連接查詢，獲取產品和賣家資訊 把新的表username那行改名叫seller_name
    $params = []; // 參數陣列，用於預處理語句
    $types = ""; // 參數類型字串，用於預處理語句


    // 如果有搜尋關鍵字，添加搜尋條件
    if (!empty($search)) {
        $sql .= " AND products.product_name LIKE ?"; // concat AND 使用 LIKE 搜尋產品名稱
        $search_param = "%$search%"; // 前後添加 % 用於模糊搜尋
        $params[] = $search_param; // 將參數添加到陣列
        $types .= "s"; // 's' 表示字串類型
    }

    // 如果選擇了特定類別，添加類別過濾條件
    if (!empty($category) && $category != 'All Categories') {
        $sql .= " AND products.category = ?";
        $params[] = $category;
        $types .= "s";
    }

    // 如果使用者已登入，排除使用者自己的產品
    if (isset($_SESSION['user_id'])) {
        $sql .= " AND products.seller_id != ?";
        $params[] = $_SESSION['user_id'];
        $types .= "s";
    }

    /****************** EXAMPLE $sql :有搜尋關鍵字、特定類別，且使用者已登入(排除自己) *************************

        SELECT products.*, users.user_name AS seller_name FROM products 
        JOIN users ON products.seller_id = users.user_id 
        WHERE 1=1 AND products.product_name LIKE ? AND products.category = ? AND products.seller_id != ?
            
    ********************************************************************************************/
    /****************** EXAMPLE $count_sql :有搜尋關鍵字、特定類別，且使用者已登入(排除自己) *******************

        SELECT COUNT(*) AS total FROM products 
        JOIN users ON products.seller_id = users.user_id 
        WHERE 1=1 AND products.product_name LIKE ? AND products.category = ? AND products.seller_id != ?
            
    ********************************************************************************************/

    // 計算符合條件的總產品數量，用於分頁
    $count_sql = str_replace("products.*, users.user_name AS seller_name", "COUNT(*) AS total", $sql);
    $stmt = $conn->prepare($count_sql);
    
    // 如果有參數，綁定參數到語句
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute(); // 執行 SQL 查詢
    $result = $stmt->get_result(); // 獲取結果集
    $row = $result->fetch_assoc(); // 獲取結果行
    $total_products = $row['total']; // 總產品數量 row['total'] value
    $total_pages = ceil($total_products / $products_per_page); // 計算總頁數，使用 ceil 向上取整
    
    // 獲取當前頁的產品 LIMIT：一次抓幾筆 OFFSET：從第幾筆開始 (0+offset)
    $sql .= " ORDER BY products.create_at DESC LIMIT ? OFFSET ?"; // 按創建時間降序排序，並限制結果數量
    $params[] = $products_per_page; // LIMIT 參數
    $params[] = $offset; // OFFSET 參數
    $types .= "ii"; // 'i' 表示整數類型

    /****************** EXAMPLE $sql :有搜尋關鍵字、特定類別，且使用者已登入(排除自己) with value *************************

        SELECT products.*, users.user_name AS seller_name FROM products 
        JOIN users ON products.seller_id = users.user_id 
        WHERE 1=1 AND products.product_name LIKE %cellphone% AND products.category = 'Electronics & Accessories' 
        AND products.seller_id != 1 ORDER BY products.create_at DESC LIMIT 8 OFFSET 0

    ********************************************************************************************/

    $stmt = $conn->prepare($sql); // 準備 SQL 語句 檢查語法
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params); // 綁定參數
    }
    
    $stmt->execute(); // 執行查詢
    $products_result = $stmt->get_result(); // 獲取結果集
    $products = []; // 用於存儲產品的陣列
    
    // 將結果集中的產品資料存入陣列
    while ($row = $products_result->fetch_assoc()) {
        // 將產品圖片路徑字串拆分成陣列（假設存儲為逗號分隔的字串）
        $row['images'] = explode(',', $row['product_image']);
        $products[] = $row; // 添加到產品陣列
    }
    
    // 輔助函數：生成分頁 URL
    function getPageUrl($page_num, $search, $category) {
        $parameters = ['page' => $page_num]; // 頁碼參數
        if (!empty($search)) {
            $parameters['search'] = $search; // 搜尋參數
        }
        if ($category != 'All Categories') {
            $parameters['category'] = $category; // 類別參數
        }
        return 'main.php?' . http_build_query($parameters); // 生成 URL 查詢字串
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Main Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <div class="main-container">
        <!-- Main Banner -->
        <div class="main-banner">
            <div class="main-page-link">
                <a href="main.php">E-Shop System</a>
            </div>
            
            <div class="product-search-area">
                <form action="main.php" method="GET">
                    <!-- value to reserve the search term -->
                    <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>
            
            <div class="user-function-area">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <?php
                        // Get user info
                        $user_id = $_SESSION['user_id'];
                        $user_sql = "SELECT user_picture, user_name FROM users WHERE user_id = ?";
                        $user_stmt = $conn->prepare($user_sql);
                        $user_stmt->bind_param("i", $user_id);
                        $user_stmt->execute();
                        $user_result = $user_stmt->get_result();
                        $user = $user_result->fetch_assoc();
                    ?>
                    <a href="cart.php" class="shopping-cart-btn"><i class="fa-solid fa-cart-shopping"></i></a>
                    <a href="user.php" class="user-name-link">
                        <?php echo ($user['user_name']);?>
                    </a>
                    
                    <div class="user-profile-pic">
                        <a href="user.php" class="user-page-link">
                            <img src="<?php echo htmlspecialchars($user['user_picture']); ?>" alt="Profile Picture" >
                        </a>
                    </div>
                    
                <?php else: ?>
                    <!-- User is not logged in -->
                    <a href="login.html" class="login-link">Login</a>
                    <strong>&nbsp/&nbsp</strong>
                    <a href="register.php" class="register-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Category Selection Buttons -->
        <!-- create 12 links if search not empty then append search keyword   -->
        <div class="category-selection">
            <?php foreach ($categories as $cat): ?>
                <a href="main.php?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>
                    category=<?php echo urlencode($cat); ?>" class="category-btn">
                    <?php echo htmlspecialchars($cat); ?> 
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Product Display Area -->
        <div class="product-display">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product.php?id=<?php echo $product['product_id']; ?>">
                            <div class="product-image">
                                <?php if (!empty($product['images'][0])): ?>
                                    <img src="<?php echo htmlspecialchars($product['images'][0]); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">No Image</div>
                                <?php endif; ?>
                                
                                <?php if (count($product['images']) > 1): ?>
                                    <div class="image-toggle">
                                        <button class="prev-img" data-product-id="<?php echo $product['product_id']; ?>">
                                            <i class="fa-solid fa-chevron-left"></i>
                                        </button>
                                        <button class="next-img" data-product-id="<?php echo $product['product_id']; ?>">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <h3 class="productname"><?php echo htmlspecialchars($product['product_name']); ?> </h3>
                                <p class="category">Category:&nbsp<?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="price">Price:&nbsp<?php echo htmlspecialchars($product['price']); ?></p>
                                <div class="stock-sold">
                                    <p><?php echo htmlspecialchars($product['in_stock']); ?>&nbsp in stock</p>
                                    <p><?php echo htmlspecialchars($product['sold']); ?>&nbsp sold</p>
                                </div>
                                <p class="seller">Seller:&nbsp<?php echo htmlspecialchars($product['seller_name']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-products">
                    <p>No products found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo getPageUrl($page - 1, $search, $category); ?>" class="page-btn prev">
                        <i class="fa-solid fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php
                // Display limited number of page links 只顯示三頁頁碼
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                // 1 ... 4 5 6
                if ($start_page > 1) {
                    echo '<a href="' . getPageUrl(1, $search, $category) . '" class="page-num">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="page-dots">...</span>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<a href="' . getPageUrl($i, $search, $category) . '" class="page-num ' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
                }
                // 1... 4 5 6 ... 10
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="page-dots">...</span>';
                    }
                    echo '<a href="' . getPageUrl($total_pages, $search, $category) . '" class="page-num">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo getPageUrl($page + 1, $search, $category); ?>" class="page-btn next">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // JavaScript for image toggling in product cards
        document.addEventListener('DOMContentLoaded', function() {
            // Store product images and current index
            const productImages = {};
            
            <?php foreach ($products as $product): ?>
                <?php if (!empty($product['images'])): ?>
                    productImages['<?php echo $product['product_id']; ?>'] = {
                        images: <?php echo json_encode($product['images']); ?>,
                        currentIndex: 0
                    };
                <?php endif; ?>
            <?php endforeach; ?>
            
            // Function to toggle image
            function toggleImage(productId, direction) {
                const productData = productImages[productId];
                if (!productData) return;
                
                if (direction === 'next') {
                    productData.currentIndex = (productData.currentIndex + 1) % productData.images.length;
                } else {
                    productData.currentIndex = (productData.currentIndex - 1 + productData.images.length) % productData.images.length;
                }
                
                const imageUrl = productData.images[productData.currentIndex];
                const imgElement = document.querySelector(`.product-card a[href="product.php?id=${productId}"] .product-image img`);
                if (imgElement) {
                    imgElement.src = imageUrl;
                }
            }
            
            // Add event listeners for prev/next buttons
            document.querySelectorAll('.prev-img').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent navigation to product page
                    e.stopPropagation(); // Stop event bubbling
                    const productId = this.getAttribute('data-product-id');
                    toggleImage(productId, 'prev');
                });
            });
            
            document.querySelectorAll('.next-img').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent navigation to product page
                    e.stopPropagation(); // Stop event bubbling
                    const productId = this.getAttribute('data-product-id');
                    toggleImage(productId, 'next');
                });
            });
        });
    </script>
</body>
</html>