# 電子商務網站專案

## 專案簡介
這是一個基於 PHP 和 MySQL 的完整電子商務系統實現。提供用戶註冊登入、個人資料管理、商品上架與管理、購物車功能、訂單處理等完整電子商務流程。

## 主要功能

### 用戶身份與認證系統
- **註冊功能**：支持用戶創建新帳號，含資料驗證功能
  - 使用者名稱和電子郵件唯一性檢查
  - 密碼可見性切換按鈕
  - 用戶圖片上傳與縮圖預覽
  - 所有空格必填
- **登入系統**：安全的用戶認證
  - 密碼加密儲存 (使用 PHP 的 password_hash 函數)
  - 密碼可見性切換按鈕
  - 錯誤提示 (帳號/密碼錯誤、欄位空白等)
- **登出功能**
- **密碼管理**：用戶可修改密碼，具備舊密碼驗證

### 用戶資料管理
- **個人資料頁面**：顯示用戶基本信息
- **資料編輯**：用戶可更新個人資料
- **帳號刪除功能**：用戶可刪除自己的帳號
  - 檢查是否有未完成訂單

### 商品管理系統
- **商品展示**：以卡片形式展示商品資訊
- **商品上架**：賣家可新增商品
  - 支持多圖片上傳
  - 11種商品類別選項
  - 價格、庫存等數據驗證
- **商品編輯**：賣家可修改已上架商品資訊
- **商品刪除**：賣家可下架商品

### 商品搜尋與瀏覽
- **主頁面商品展示**：grid顯示商品
- **分類搜尋**：通過類別篩選商品
- **關鍵字搜尋**：通過商品名稱搜尋
- **分頁功能**：支持大量商品的瀏覽
- **商品詳情頁**：顯示完整商品資訊及購買選項

### 購物車系統
- **依賣家分類**：購物車自動按賣家分組商品
- **購買數量控制**：限制最大購買數量不超過庫存
- **購物車管理**：可刪除購物車中的商品
- **結帳流程**：完整的商品訂購流程

### 訂單處理系統
- **買家訂單管理**：查看所有已購買訂單
- **賣家訂單管理**：管理所有銷售訂單
- **訂單狀態追蹤**：
  - 賣家確認出貨功能
  - 買家確認收貨功能
- **歷史訂單記錄**：完成的訂單保存在歷史記錄中

## 技術實現
- **前端技術**：
  - HTML
  - CSS
  - JavaScript
- **後端技術**：
  - PHP 處理表單提交與資料庫操作
  - Session管理用戶登入狀態
  - 檔案上傳處理與驗證
- **資料庫設計**：
  - MySQL 關聯式資料庫
  - 三個主要資料表 (users, products, orders)
  - 資料完整性與關聯性維護

## 資料庫結構

### users Table
- `user_id` (int): 用戶唯一識別碼
- `user_name` (varchar): 用戶名稱
- `account` (varchar): 登入帳號 (電子郵件)
- `hash_password` (varchar): 加密後的密碼
- `user_picture` (varchar): 用戶圖片路徑
- `fullname` (varchar): 用戶全名
- `address` (varchar): 用戶地址
- `phone` (varchar): 聯絡電話

### products Table
- `product_id` (int): 商品唯一識別碼
- `product_name` (varchar): 商品名稱
- `category` (varchar): 商品類別
- `seller_id` (varchar): 賣家 ID
- `description` (text): 商品描述
- `product_image` (text): 商品圖片路徑
- `in_stock` (int): 庫存數量
- `sold` (int): 已售出數量
- `price` (int): 商品價格
- `create_at` (timestamp): 建立時間
- `update_at` (timestamp): 更新時間

### orders Table
- `order_id` (varchar/int): 訂單唯一識別碼
- `seller_id` (varchar/int): 賣家 ID
- `buyer_id` (varchar/int): 買家 ID
- `order_price` (int): 訂單總價
- `pay_method` (varchar): 付款方式
- `buyer_fullname` (varchar): 買家全名
- `buyer_phone` (varchar): 買家電話
- `buyer_address` (varchar): 買家地址
- `order_products` (text): 訂單商品資訊
- `status` (varchar): 訂單狀態
- `checkout_at` (timestamp): 結帳時間
- `completed_at` (timestamp): 完成時間

