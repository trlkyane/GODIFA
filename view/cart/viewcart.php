<?php
// Middleware: Chỉ cho phép khách hàng truy cập
require_once __DIR__ . '/../../middleware/customer_only.php';

// Session đã được start ở middleware
$pageTitle = "Giỏ hàng";
include __DIR__ . '/../layout/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  
  <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-12 brand-font">Giỏ hàng</h1>

  <?php if (!empty($_SESSION['cart'])): ?>
    <div class="grid lg:grid-cols-3 gap-10">
      
      <!-- Cart Items -->
      <div class="lg:col-span-2 space-y-6">
        <?php
        $tongTien = 0;
        foreach ($_SESSION['cart'] as $productId => $item):
          $thanhTien = $item['price'] * $item['quantity'];
          $tongTien += $thanhTien;
        ?>
          <div class="flex gap-6 pb-6 border-b border-gray-100 cart-item" id="item-<?= $productId ?>">
            <!-- Product Image -->
            <div class="w-24 h-24 flex-shrink-0 bg-gray-50 rounded-sm overflow-hidden">
              <img src="/GODIFA/image/<?= htmlspecialchars($item['image']) ?>" 
                   alt="<?= htmlspecialchars($item['productName']) ?>"
                   class="w-full h-full object-cover">
            </div>

            <!-- Product Info -->
            <div class="flex-1 min-w-0">
              <h3 class="text-base font-semibold text-gray-900 mb-2">
                <?= htmlspecialchars($item['productName']) ?>
              </h3>
              <p class="text-sm text-gray-500 mb-4">
                <?= number_format($item['price'], 0, ',', '.') ?>₫
              </p>

              <div class="flex items-center justify-between">
                <!-- Quantity Controls -->
                <div class="flex items-center border border-gray-200">
                  <button onclick="changeQty(<?= $productId ?>, -1)" 
                          class="px-3 py-2 hover:bg-gray-50 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                  </button>
                  <input type="number" 
                         value="<?= $item['quantity'] ?>" 
                         min="1" 
                         class="w-12 text-center py-2 border-x border-gray-200 focus:outline-none text-sm"
                         id="qty-<?= $productId ?>"
                         onchange="updateQuantity(<?= $productId ?>, this.value)">
                  <button onclick="changeQty(<?= $productId ?>, 1)" 
                          class="px-3 py-2 hover:bg-gray-50 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                  </button>
                </div>

                <!-- Remove Button -->
                <button onclick="removeItem(<?= $productId ?>)" 
                        class="text-sm text-gray-500 hover:text-black transition underline">
                  Xóa
                </button>
              </div>
            </div>

            <!-- Subtotal -->
            <div class="text-right flex-shrink-0">
              <p class="text-base font-semibold text-gray-900" id="subtotal-<?= $productId ?>">
                <?= number_format($thanhTien, 0, ',', '.') ?>₫
              </p>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Clear All Button -->
        <div class="pt-4">
          <button onclick="clearAllCart()" 
                  class="text-sm text-gray-500 hover:text-black transition underline">
            Xóa tất cả sản phẩm
          </button>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="lg:col-span-1">
        <div class="bg-gray-50 rounded-sm p-6 sticky top-24">
          <h2 class="text-lg font-bold text-gray-900 mb-6">Tóm tắt đơn hàng</h2>
          
          <div class="space-y-3 mb-6">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Tạm tính</span>
              <span class="font-medium text-gray-900" id="subtotalPrice">
                <?= number_format($tongTien, 0, ',', '.') ?>₫
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Phí vận chuyển</span>
              <span class="font-medium text-gray-900">Tính sau</span>
            </div>
          </div>

          <div class="border-t border-gray-200 pt-4 mb-6">
            <div class="flex justify-between">
              <span class="text-base font-bold text-gray-900">Tổng cộng</span>
              <span class="text-xl font-bold text-gray-900" id="totalPrice">
                <?= number_format($tongTien, 0, ',', '.') ?>₫
              </span>
            </div>
          </div>

          <a href="/GODIFA/view/cart/checkout.php" 
             class="block w-full bg-black text-white text-center py-4 text-sm font-bold uppercase tracking-wide hover:bg-gray-800 transition rounded-sm">
            Thanh toán
          </a>

          <a href="/GODIFA/view/product/list.php" 
             class="block w-full text-center py-3 text-sm text-gray-600 hover:text-black transition mt-3">
            Tiếp tục mua sắm
          </a>
        </div>
      </div>

    </div>
  <?php else: ?>
    <div class="text-center py-20">
      <svg class="mx-auto h-24 w-24 text-gray-200 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
      </svg>
      <h2 class="text-2xl font-bold text-gray-900 mb-3 brand-font">Giỏ hàng trống</h2>
      <p class="text-gray-500 mb-8">Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm</p>
      <a href="/GODIFA/view/product/list.php" 
         class="inline-block bg-black text-white px-8 py-3 text-sm font-bold uppercase tracking-wide hover:bg-gray-800 transition rounded-sm">
        Khám phá sản phẩm
      </a>
    </div>
  <?php endif; ?>

</main>

<script>
function changeQty(productId, delta) {
    const input = document.getElementById('qty-' + productId);
    const newQty = parseInt(input.value) + delta;
    if (newQty >= 1) {
        input.value = newQty;
        updateQuantity(productId, newQty);
    }
}

// Hàm cập nhật giao diện giỏ hàng
function updateCartDisplay(cart) {
    let totalPrice = 0;
    
    // Cập nhật từng sản phẩm
    for (let productId in cart) {
        let item = cart[productId];
        let subtotal = item.price * item.quantity;
        totalPrice += subtotal;
        
        // Cập nhật số lượng
        let qtyInput = document.getElementById('qty-' + productId);
        if (qtyInput) {
            qtyInput.value = item.quantity;
        }
        
        // Cập nhật thành tiền
        let subtotalEl = document.getElementById('subtotal-' + productId);
        if (subtotalEl) {
            subtotalEl.textContent = subtotal.toLocaleString('vi-VN') + '₫';
        }
    }
    
    // Cập nhật tổng tiền
    const totalEl = document.getElementById('totalPrice');
    const subtotalEl = document.getElementById('subtotalPrice');
    if (totalEl) totalEl.textContent = totalPrice.toLocaleString('vi-VN') + '₫';
    if (subtotalEl) subtotalEl.textContent = totalPrice.toLocaleString('vi-VN') + '₫';
    
    // Cập nhật badge số lượng ở header
    let cartBadges = document.querySelectorAll('.cart-count');
    let itemCount = Object.keys(cart).length;
    cartBadges.forEach(badge => {
        badge.textContent = itemCount;
        if (itemCount === 0) {
            badge.style.display = 'none';
        }
    });
}

function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        alert('Số lượng phải lớn hơn 0');
        return;
    }
    
    fetch('/GODIFA/controller/cCart.php?action=update', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `productId=${productId}&quantity=${quantity}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Cập nhật giao diện không cần reload
            if (data.cart) {
                updateCartDisplay(data.cart);
            } else {
                console.error('Cart data not found in response');
                location.reload();
            }
        } else {
            alert('❌ ' + (data.message || 'Cập nhật thất bại!'));
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Lỗi kết nối!');
        location.reload();
    });
}

function removeItem(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
    
    fetch('/GODIFA/controller/cCart.php?action=remove', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `productId=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Cập nhật giao diện không cần reload
            if (data.cart && Object.keys(data.cart).length > 0) {
                updateCartDisplay(data.cart);
                
                // Xóa dòng sản phẩm khỏi bảng
                let row = document.getElementById('qty-' + productId);
                if (row) {
                    row.closest('tr').remove();
                }
            } else {
                // Giỏ hàng trống, reload để hiển thị thông báo
                location.reload();
            }
        } else {
            alert('❌ Xóa thất bại!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Lỗi kết nối!');
    });
}

function clearAllCart() {
    if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) return;
    
    fetch('/GODIFA/controller/cCart.php?action=clear', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Đã xóa toàn bộ giỏ hàng!');
            location.reload();
        } else {
            alert('❌ Xóa thất bại!');
        }
    })
    .catch(() => alert('❌ Lỗi kết nối!'));
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
