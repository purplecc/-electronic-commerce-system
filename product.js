document.addEventListener('DOMContentLoaded', function() {
    // Handle product image gallery
    const thumbnails = document.querySelectorAll('.thumbnail');
    const currentImage = document.getElementById('current-image');
    
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            // Remove active class from all thumbnails
            thumbnails.forEach(t => t.classList.remove('active'));
            // Add active class to clicked thumbnail
            this.classList.add('active');
            
            // Update main image
            const imageUrl = this.querySelector('img').src;
            currentImage.src = imageUrl;
        });
    });
    
    // Handle quantity input
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('quantity-decrease');
    const increaseBtn = document.getElementById('quantity-increase');
    // const maxStock = <?php echo (int)$product['in_stock']; ?>;
    
    // Function to update quantity
    function updateQuantity(value) {
        // Ensure value is a number and not less than 1
        value = Math.max(1, parseInt(value) || 1);
        
        // Ensure value does not exceed available stock
        value = Math.min(value, maxStock);
        
        // Update input value
        quantityInput.value = value;
    }
    
    // Event listeners for quantity buttons
    decreaseBtn.addEventListener('click', function() {
        updateQuantity(parseInt(quantityInput.value) - 1);
        if(quantityInput.value == maxStock) {
            const warn = document.getElementById('quantity-warning');
            warn.innerHTML = '';
            
            var message = document.createElement('p');
            message.innerHTML = 'Reach maximum perchase limit!';
            message.style.color = 'red';
            message.style.marginTop = '10px';
            warn.appendChild(message);
        } else {
            const warn = document.getElementById('quantity-warning');
            warn.innerHTML = '';
        }
    });
    
    increaseBtn.addEventListener('click', function() {
        updateQuantity(parseInt(quantityInput.value) + 1);
        if(quantityInput.value == maxStock) {
            const warn = document.getElementById('quantity-warning');
            warn.innerHTML = '';
            
            var message = document.createElement('p');
            message.innerHTML = 'Reach maximum perchase limit!';
            message.style.color = 'red';
            message.style.marginTop = '10px';
            warn.appendChild(message);
        } else {
            const warn = document.getElementById('quantity-warning');
            warn.innerHTML = '';
        }
    });

    
    // Handle direct input
    quantityInput.addEventListener('input', function() {
        // Remove non-numeric characters
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Ensure value is not empty
        if (this.value === '') {
            this.value = '1';
        } else {
            // Update quantity based on input not exceeding max stock
            updateQuantity(this.value);
            if(this.value == maxStock) {
                const warn = document.getElementById('quantity-warning');
                warn.innerHTML = '';
                
                var message = document.createElement('p');
                message.innerHTML = 'Reach maximum perchase limit!';
                message.style.color = 'red';
                message.style.marginTop = '10px';
                warn.appendChild(message);
            } else {
                const warn = document.getElementById('quantity-warning');
                warn.innerHTML = '';
            }
        }

    });

    quantityInput.addEventListener('change', function() {
        // console.log(this.value);
        if(quantityInput.value == maxStock) {
            const warn = document.getElementById('quantity-warning');
            warn.innerHTML = '';
            
            var message = document.createElement('p');
            message.innerHTML = 'Reach maximum perchase limit!';
            message.style.color = 'red';
            message.style.marginTop = '10px';
            warn.appendChild(message);
        } else {
            const warn = document.getElementById('quantity-warning');
            warn.innerHTML = '';
        }
    });

    // Validate on blur
    quantityInput.addEventListener('blur', function() {
        updateQuantity(this.value);
    });
    
    // Validate on form submit
    document.querySelector('.add-to-cart-form').addEventListener('submit', function(e) {
        updateQuantity(quantityInput.value);
        
        // Show warning if quantity exceeds stock
        if (parseInt(quantityInput.value) > maxStock) {
            alert('Maximum purchase quantity is ' + maxStock + '!');
        }
    });

    window.toggleImage = function(productId, direction) {
        const productData = productImagesData[productId];
        if (!productData) return;
        
        if (direction === 'next') {
            productData.currentIndex = (productData.currentIndex + 1) % productData.images.length;
        } else {
            productData.currentIndex = (productData.currentIndex - 1 + productData.images.length) % productData.images.length;
        }
        
        const imageUrl = productData.images[productData.currentIndex];
        const imgElement = document.querySelector(`.product-gallery .main-image img `);
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
