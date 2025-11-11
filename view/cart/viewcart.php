<?php
// Middleware: Chỉ cho phép khách hàng truy cập
require_once __DIR__ . '/../../middleware/customer_only.php';

// Session đã được start ở middleware
$pageTitle = "Giỏ hàng";
include __DIR__ . '/../layout/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8">
  <h2 class="text-2xl font-bold mb-6 text-blue-700">🛍️ Giỏ hàng của bạn</h2>

  <?php if (!empty($_SESSION['cart'])): ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow p-4">
      <table class="min-w-full table-auto border-collapse">
        <thead class="bg-blue-100 text-gray-700 text-sm uppercase">
          <tr>
            <th class="border px-4 py-2 text-left">Ảnh</th>
            <th class="border px-4 py-2 text-left">Tên sản phẩm</th>
            <th class="border px-4 py-2 text-center">Giá</th>
            <th class="border px-4 py-2 text-center">SL</th>
            <th class="border px-4 py-2 text-right">Thành tiền</th>
            <th class="border px-4 py-2 text-center">Xóa</th>
          </tr>
        </thead>
        <tbody class="text-sm">
          <?php
          $tongTien = 0;
          foreach ($_SESSION['cart'] as $productId => $item):
            $thanhTien = $item['price'] * $item['quantity'];
            $tongTien += $thanhTien;
          ?>
            <tr class="hover:bg-gray-50">
              <td class="border px-4 py-2">
                <img src="/GODIFA/image/<?= htmlspecialchars($item['image']) ?>" width="60" class="rounded">
              </td>
              <td class="border px-4 py-2 font-medium"><?= htmlspecialchars($item['productName']) ?></td>
              <td class="border px-4 py-2 text-center text-blue-600 font-bold"><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
              <td class="border px-4 py-2 text-center">
                <input type="number" value="<?= $item['quantity'] ?>" min="1" 
                       class="w-16 text-center border rounded px-2 py-1"
                       id="qty-<?= $productId ?>"
                       onchange="updateQuantity(<?= $productId ?>, this.value)">
              </td>
              <td class="border px-4 py-2 text-right font-bold" id="subtotal-<?= $productId ?>"><?= number_format($thanhTien, 0, ',', '.') ?>đ</td>
              <td class="border px-4 py-2 text-center">
                <button onclick="removeItem(<?= $productId ?>)" 
                        class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="text-right mt-6">
        <p class="text-lg font-bold text-gray-700">Tổng tiền: <span class="text-green-600" id="totalPrice"><?= number_format($tongTien, 0, ',', '.') ?>đ</span></p>
        <div class="mt-4 space-x-3">
          <button onclick="clearAllCart()" class="bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600 transition">
            <i class="fas fa-trash mr-2"></i>Xóa tất cả
          </button>
          <a href="/GODIFA/view/cart/checkout.php" class="inline-block bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
            <i class="fas fa-credit-card mr-2"></i>Thanh toán
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="text-center py-12">
      <i class="fas fa-shopping-cart text-gray-400 text-6xl mb-4"></i>
      <p class="text-xl text-gray-600 mb-4">🛒 Giỏ hàng của bạn đang trống</p>
      <a href="/GODIFA/view/product/list.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
        <i class="fas fa-shopping-bag mr-2"></i>Tiếp tục mua sắm
      </a>
    </div>
  <?php endif; ?>
</div>

<script>
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
            subtotalEl.textContent = subtotal.toLocaleString('vi-VN') + 'đ';
        }
    }
    
    // Cập nhật tổng tiền
    let totalEl = document.getElementById('totalPrice');
    if (totalEl) {
        totalEl.textContent = totalPrice.toLocaleString('vi-VN') + 'đ';
    }
    
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
