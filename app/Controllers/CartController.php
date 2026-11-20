<?php
/**
 * CartController - Giỏ hàng
 */
class CartController extends Controller {
    private $cartModel;
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->cartModel = new CartModel();
        $this->productModel = new ProductModel();
    }
    
    private function getSessionId() {
        if (!isset($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = session_id();
        }
        return $_SESSION['cart_session_id'];
    }
    
    private function getCart() {
        $userId = $this->isLoggedIn() ? $this->getUserId() : null;
        $sessionId = $this->getSessionId();
        return $this->cartModel->getOrCreate($userId, $sessionId);
    }
    
    public function index() {
        // Clear Buy Now state if viewing the shopping cart
        unset($_SESSION['buy_now']);
        
        $cart = $this->getCart();
        $items = $this->cartModel->getItems($cart['id']);
        
        $subtotal = 0;
        foreach ($items as &$item) {
            $price = (!empty($item['price']) && (float)$item['price'] > 0)
                ? (float)$item['price']
                : (float)($item['price_discount'] ?? $item['price_sale']);
            $item['current_price'] = $price;
            $item['stock_quantity'] = max(0, (int)$item['stock_quantity']);
            $item['is_out_of_stock'] = $item['status'] !== 'active' || $item['stock_quantity'] === 0;
            $item['is_quantity_unavailable'] = !$item['is_out_of_stock'] && (int)$item['quantity'] > $item['stock_quantity'];
            if ($item['is_quantity_unavailable']) {
                $item['quantity'] = $item['stock_quantity'];
                $this->cartModel->updateItem($cart['id'], $item['id'], $item['quantity']);
            }
            $item['item_total'] = $price * $item['quantity'];
            if (!$item['is_out_of_stock']) {
                $subtotal += $item['item_total'];
            }
        }
        
        $shippingFee = $subtotal >= getShippingFeeFreeThreshold() ? 0 : getShippingFeeStandard();
        
        // Tự động kiểm tra và tính toán lại hoặc hủy coupon trong session khi giỏ hàng đổi
        $discount = 0;
        if (isset($_SESSION['coupon'])) {
            $couponModel = new CouponModel();
            $coupon = $couponModel->findById($_SESSION['coupon']['id']);
            if ($coupon && $coupon['status'] === 'active' && strtotime($coupon['start_date']) <= time() && strtotime($coupon['end_date']) >= time()) {
                $discountAmt = $couponModel->calculateDiscount($coupon, $subtotal);
                if ($discountAmt > 0) {
                    $_SESSION['coupon']['discount'] = $discountAmt;
                    $discount = $discountAmt;
                } else {
                    unset($_SESSION['coupon']);
                }
            } else {
                unset($_SESSION['coupon']);
            }
        }
        
        $this->view('cart.index', [
            'title' => 'Giỏ hàng - ' . APP_NAME,
            'items' => $items,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'discount' => $discount,
            'total' => $subtotal + $shippingFee - $discount,
            'cart' => $cart,
        ]);
    }
    
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid method'], 405);
            return;
        }

        $productId = filter_var($this->getInput('product_id', 0), FILTER_VALIDATE_INT);
        $quantity = filter_var($this->getInput('quantity', 1), FILTER_VALIDATE_INT);
        $variant = trim(sanitize($this->getInput('variant', '')));
        $variantIdRaw = $this->getInput('variant_id', '');
        $variantId = ($variantIdRaw !== '' && is_numeric($variantIdRaw)) ? (int)$variantIdRaw : null;
        if ($productId === false || $quantity === false || $productId < 1 || $quantity < 1) {
            $this->json(['success' => false, 'message' => 'Sản phẩm hoặc số lượng không hợp lệ'], 400);
            return;
        }

        $product = $this->productModel->findById($productId);

        if (!$product || $product['status'] !== 'active') {
            $this->json(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            return;
        }

        // Giá và tồn kho luôn xác định từ database, không tin dữ liệu client gửi lên.
        $variantRecord = $variantId !== null ? $this->productModel->getVariantById($variantId, $productId) : null;
        if ($variantId !== null && !$variantRecord) {
            $this->json(['success' => false, 'message' => 'Phiên bản sản phẩm không hợp lệ hoặc đã bị ngừng bán']);
            return;
        }

        $productVariants = $this->productModel->getVariants($productId);
        // Chỉ bắt buộc chọn phiên bản khi sản phẩm có SKU (biến thể chính thức).
        // Màu/dung lượng trong variants_json chỉ mang tính mô tả (không có giá/tồn riêng)
        // nên không bắt buộc variant_id.
        if (!empty($productVariants) && $variantRecord === null) {
            $this->json(['success' => false, 'message' => 'Vui lòng chọn phiên bản sản phẩm trước khi thêm vào giỏ hàng']);
            return;
        }

        $stockQuantity = $variantRecord !== null
            ? max(0, (int)$variantRecord['stock_quantity'])
            : max(0, (int)$product['stock_quantity']);
        if ($stockQuantity < 1) {
            $this->json(['success' => false, 'message' => 'Phiên bản này đã hết hàng']);
            return;
        }

        $cart = $this->getCart();
        $cartQuantity = $this->cartModel->getProductQuantity($cart['id'], $productId, $variantId, $variant);
        if ($cartQuantity + $quantity > $stockQuantity) {
            $this->json(['success' => false, 'message' => 'Sản phẩm không đủ hàng. Tồn kho: ' . $stockQuantity]);
            return;
        }

        $basePrice = (float)($product['price_discount'] ?? $product['price_sale']);
        $finalPrice = max(0, $basePrice + (float)($variantRecord['price_adjustment'] ?? 0));
        $this->cartModel->addItem($cart['id'], $productId, $quantity, $finalPrice, $variantId, $variant);
        
        // Cập nhật cart count
        $cartCount = $this->cartModel->getItemCount($cart['id']);
        $_SESSION['cart_count'] = $cartCount;
        
        $variantText = !empty($variant) ? ' (' . $variant . ')' : '';
        $this->json([
            'success' => true,
            'message' => 'Đã thêm ' . e($product['name'] . $variantText) . ' vào giỏ hàng',
            'cartCount' => $cartCount,
        ]);
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        $this->validateCsrf();
        
        $itemId = filter_var($this->getInput('item_id', 0), FILTER_VALIDATE_INT);
        $quantity = filter_var($this->getInput('quantity', 1), FILTER_VALIDATE_INT);
        if ($itemId === false || $quantity === false || $itemId < 1 || $quantity < 1) {
            $this->json(['success' => false, 'message' => 'Sản phẩm hoặc số lượng không hợp lệ'], 400);
            return;
        }
        
        $cart = $this->getCart();
        $cartItem = $this->cartModel->getItemForCart($cart['id'], $itemId);
        if (!$cartItem || $cartItem['status'] !== 'active') {
            $this->json(['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng'], 404);
            return;
        }
        if ($quantity > max(0, (int)$cartItem['stock_quantity'])) {
            $this->json(['success' => false, 'message' => 'Số lượng vượt quá tồn kho. Tồn kho: ' . (int)$cartItem['stock_quantity']], 400);
            return;
        }
        $this->cartModel->updateItem($cart['id'], $itemId, $quantity);
        
        // Recalculate - dùng giá đã lưu trong cart_items (đã gồm chênh lệch phiên bản)
        $items = $this->cartModel->getItems($cart['id']);
        $subtotal = 0;
        $itemTotal = 0;
        foreach ($items as $item) {
            $price = (!empty($item['price']) && (float)$item['price'] > 0)
                ? (float)$item['price']
                : (float)($item['price_discount'] ?? $item['price_sale']);
            $itemSubtotal = $price * $item['quantity'];
            $subtotal += $itemSubtotal;
            if ($item['id'] == $itemId) {
                $itemTotal = $itemSubtotal;
            }
        }
        $shippingFee = $subtotal >= getShippingFeeFreeThreshold() ? 0 : getShippingFeeStandard();
        
        $discount = 0;
        $couponCode = '';
        if (isset($_SESSION['coupon'])) {
            $couponModel = new CouponModel();
            $coupon = $couponModel->findById($_SESSION['coupon']['id']);
            if ($coupon && $coupon['status'] === 'active' && strtotime($coupon['start_date']) <= time() && strtotime($coupon['end_date']) >= time()) {
                $discountAmt = $couponModel->calculateDiscount($coupon, $subtotal);
                if ($discountAmt > 0) {
                    $_SESSION['coupon']['discount'] = $discountAmt;
                    $discount = $discountAmt;
                    $couponCode = $coupon['coupon_code'];
                } else {
                    unset($_SESSION['coupon']);
                }
            } else {
                unset($_SESSION['coupon']);
            }
        }
        
        $cartCount = $this->cartModel->getItemCount($cart['id']);
        $_SESSION['cart_count'] = $cartCount;
        
        $this->json([
            'success' => true,
            'subtotal' => formatPrice($subtotal),
            'shippingFee' => formatPrice($shippingFee),
            'discount' => formatPrice($discount),
            'discountRaw' => $discount,
            'couponCode' => $couponCode,
            'total' => formatPrice($subtotal + $shippingFee - $discount),
            'cartCount' => $cartCount,
            'itemTotal' => formatPrice($itemTotal)
        ]);
    }
    
    public function remove() {
        $this->validateCsrf();
        $itemId = (int)$this->getInput('item_id', 0);
        $cart = $this->getCart();
        $this->cartModel->removeItem($cart['id'], $itemId);
        
        $cartCount = $this->cartModel->getItemCount($cart['id']);
        $_SESSION['cart_count'] = $cartCount;
        setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            $this->json(['success' => true, 'cartCount' => $cartCount]);
        } else {
            $this->redirect(url('cart'));
        }
    }
    
    public function count() {
        $this->json(['count' => getCartCount()]);
    }
}
?>
