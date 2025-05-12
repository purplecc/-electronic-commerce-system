document.addEventListener('DOMContentLoaded', function() {

    function showThumbnail() {
        var file = thumbnailinput.files[0];
        var reader = new FileReader();
        var preview = document.getElementById('preview');
        preview.innerHTML = ""; // Clear previous images
        
        reader.onload = function(event) {                
            var image = document.createElement('img');
            image.src = event.target.result;            // file reader 讀取的結果 (data url), event.target是trigger event的物件 (FileReader)
            image.style.width = "80px";               // set the width of the image
            image.style.height = "80px";             // set the height of the image
            image.style.borderRadius = "10px";         // set the border radius to make it circular
            image.style.marginTop = "5px";            // set the margin top to 10px
            preview.appendChild(image);                 // append the image to the preview div
        }
        // 寫在onload後面 當read完之後才知道要做什麼
        reader.readAsDataURL(file);                     // read the file as a data URL
    
    }

    // Basic information edit form toggle
    const editInfoBtn = document.getElementById('edit-info-btn');
    const editInfoForm = document.getElementById('edit-info-form');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const thumbnailinput = document.getElementById('picture');
    
    if (editInfoBtn) {
        editInfoBtn.addEventListener('click', function() {
            editInfoBtn.style.display = 'none';
            editInfoForm.style.display = 'block';
            var preview = document.getElementById('preview');
            preview.innerHTML = ""; // Clear previous images
            thumbnailinput.value = ""; // Clear the file input
        });
    }
    
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            editInfoForm.style.display = 'none';
            editInfoBtn.style.display = 'block';
            var preview = document.getElementById('preview');
            preview.innerHTML = ""; // Clear previous images
            thumbnailinput.value = ""; // Clear the file input
        });
    }
    if (thumbnailinput) {
        thumbnailinput.addEventListener('change', showThumbnail);
    }

    
    // Password toggle function
    window.togglePassword = function(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    };
    
    // Add product form toggle
    const addProductBtn = document.getElementById('add-product-btn');
    const addProductForm = document.getElementById('add-product-form');
    const cancelAddProduct = document.getElementById('cancel-add-product');
    
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            addProductForm.style.display = 'block';
        });
    }
    
    if (cancelAddProduct) {
        cancelAddProduct.addEventListener('click', function() {
            addProductForm.style.display = 'none';
        });
    }
    
    // Product image preview
    const productImages = document.getElementById('product_images');
    
    if (productImages) {
        productImages.addEventListener('change', function() {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('thumbnail');
                        preview.appendChild(img);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
        });
    }
    
    // Edit product buttons
    const editProductBtns = document.querySelectorAll('.edit-product-btn');
    const cancelEditBtns = document.querySelectorAll('.cancel-edit-btn');
    
    editProductBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const editForm = document.getElementById(`edit-form-${productId}`);
            
            if (editForm) {
                editForm.style.display = 'block';
                this.closest('.product-card').querySelector('.product-info').style.display = 'none';
            }
        });
    });
    
    cancelEditBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const editForm = document.getElementById(`edit-form-${productId}`);
            
            if (editForm) {
                editForm.style.display = 'none';
                this.closest('.product-card').querySelector('.product-info').style.display = 'block';
            }
        });
    });
    
    // Product image preview in edit forms
    document.querySelectorAll('input[type="file"][id^="edit_images_"]').forEach(input => {
        input.addEventListener('change', function() {
            const preview = this.nextElementSibling;
            preview.innerHTML = '';
            
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('thumbnail');
                        preview.appendChild(img);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
        });
    });
    
    // Delete product modal
    const deleteProductBtns = document.querySelectorAll('.delete-product-btn');
    const deleteModal = document.getElementById('delete-modal');
    const cancelDelete = document.getElementById('cancel-delete');
    const deleteProductName = document.getElementById('delete-product-name');
    const deleteProductId = document.getElementById('delete-product-id');
    
    deleteProductBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            
            deleteProductName.textContent = productName;
            deleteProductId.value = productId;
            deleteModal.style.display = 'flex';
        });
    });
    
    if (cancelDelete) {
        cancelDelete.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
    }
    
    // Order tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const orderTabs = document.querySelectorAll('.order-tab');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and tabs
            tabBtns.forEach(btn => btn.classList.remove('active'));
            orderTabs.forEach(tab => tab.classList.remove('active'));
            
            // Add active class to clicked button and corresponding tab
            this.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
    
    // Delete account confirmation
    const deleteAccountBtn = document.getElementById('delete-account-btn');
    const deleteAccountModal = document.getElementById('delete-account-modal');
    const cancelDeleteAccount = document.getElementById('cancel-delete-account');
    const confirmDeleteAccount = document.getElementById('confirm-delete-account');
    const deleteAccountForm = document.getElementById('delete-account-form');
    
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function() {
            deleteAccountModal.style.display = 'flex';
        });
    }
    
    if (cancelDeleteAccount) {
        cancelDeleteAccount.addEventListener('click', function() {
            deleteAccountModal.style.display = 'none';
        });
    }
    
    if (confirmDeleteAccount) {
        confirmDeleteAccount.addEventListener('click', function() {
            deleteAccountForm.submit();
            console.log('Form submitted');
        });
    }
    
    // Product image toggle functionality
    // This part of the code uses productImagesData which is generated in the PHP template
    // Make sure the variable is defined in user.php before including this script
    
    // Function to toggle image
    window.toggleImage = function(productId, direction) {
        const productData = productImagesData[productId];
        if (!productData) return;
        
        if (direction === 'next') {
            productData.currentIndex = (productData.currentIndex + 1) % productData.images.length;
        } else {
            productData.currentIndex = (productData.currentIndex - 1 + productData.images.length) % productData.images.length;
        }
        
        const imageUrl = productData.images[productData.currentIndex];
        const imgElement = document.querySelector(`.product-card[data-product-id="${productId}"] .product-image img`);
        if (imgElement) {
            imgElement.src = imageUrl;
        }
    };
    
    // Add event listeners for prev/next buttons
    document.querySelectorAll('.prev-img').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default action
            e.stopPropagation(); // Stop event bubbling
            const productId = this.getAttribute('data-product-id');
            toggleImage(productId, 'prev');
        });
    });
    
    document.querySelectorAll('.next-img').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default action
            e.stopPropagation(); // Stop event bubbling
            const productId = this.getAttribute('data-product-id');
            toggleImage(productId, 'next');
        });
    });
    
    // Add product-id to product cards for image toggle function
    document.querySelectorAll('.product-card').forEach(card => {
        const productIdEl = card.querySelector('.edit-product-btn');
        if (productIdEl) {
            const productId = productIdEl.getAttribute('data-product-id');
            card.setAttribute('data-product-id', productId);
        }
    });
});