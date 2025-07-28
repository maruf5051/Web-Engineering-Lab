const productCards = document.querySelectorAll('.product-card');

productCards.forEach(card => {
    const regularPriceText = card.querySelector('.regular-price').textContent.replace("৳", "");
    const discountPriceText = card.querySelector('.discount-price').textContent.replace("৳", "");
    
    const regularPrice = parseFloat(regularPriceText);
    const discountPrice = parseFloat(discountPriceText);

    // Calculate discount
    const discountPercent = ((regularPrice - discountPrice) / regularPrice) * 100;
    
    // Update badge
    card.querySelector('.discount-bedge').textContent = `${discountPercent}% OFF`;   
})



// Cart functionality
  const addToCartButtons = document.querySelectorAll('.add-to-cart');
  const cartIcon = document.querySelector('.cart-icon');
  const cartCount = cartIcon.querySelector('sub');

  const cartSidebar = document.getElementById('cartSidebar');
  const cartItemsDiv = document.getElementById('cartItems');
  const totalPriceDisplay = document.getElementById('totalPrice');

  let count = 0;
  let totalPrice = 0;
  let cartItems = [];

  // Add to cart button click
  addToCartButtons.forEach(button => {
    button.addEventListener('click', function() {
      const card = this.parentElement;
      const productName = card.querySelector('h3').innerText;
      const regularPriceText = card.querySelector('.regular-price').innerText.replace('৳', '');
      const discountPriceElement = card.querySelector('.discount-price');

      let finalPrice;

      if (discountPriceElement) {
        finalPrice = parseFloat(discountPriceElement.innerText.replace('৳', ''));
      } else {
        finalPrice = parseFloat(regularPriceText);
      }
      count++;
      cartCount.innerText = count;
      totalPrice += finalPrice;
      alert(`${productName} has been added to your cart!`);
      cartItems.push({ name: productName, price: finalPrice });
    });
  });

  // Show sidebar when clicking cart icon
  cartIcon.addEventListener('click', function() {
    updateCartSidebar(); // update cart before showing
    cartSidebar.classList.add('show');
  });

  // Close sidebar function
  function closeCart() {
    cartSidebar.classList.remove('show');
  }

  // Update cart sidebar display
  function updateCartSidebar() {
    cartItemsDiv.innerHTML = "";
    cartItems.forEach(item => {
      const div = document.createElement('div');
      div.innerText = item.name + " - ৳" + item.price.toFixed(2);
      cartItemsDiv.appendChild(div);
    });
    totalPriceDisplay.innerText = "Total: ৳" + totalPrice.toFixed(2);
  }
