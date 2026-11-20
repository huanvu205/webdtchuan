
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

    /**
     * Giá mặc định của sản phẩm khi không sử dụng SKU.
     */
    private function getBaseProductPrice($product) {
        if (
            isset($product['price_discount']) &&
            $product['price_discount'] !== null &&
            (float)$product['price_discount'] > 0
        ) {
            return (float)$product['price_discount'];
        }

        return (float)($product['price_sale'] ?? 0);
    }

    public function index() {
        unset($_SESSION['buy_now']);

        $cart = $this->getCart();
        $items = $this->cartModel->getItems($cart['id']);

        $subtotal = 0;

        foreach ($items as &$item) {
            $price = (
                isset($item['price']) &&
                (float)$item['price'] > 0
            )
                ? (float)$item['price']
                : $this->getBaseProductPrice($item);

            $item['current_price'] = $price;
            $item['stock_quantity'] = max(
                0,
                (int)$item['stock_quantity']
            );

            $item['is_out_of_stock'] =
                $item['status'] !== 'active' ||
                $item['stock_quantity'] === 0;

            $item['is_quantity_unavailable'] =
                !$item['is_out_of_stock'] &&
                (int)$item['quantity'] > $item['stock_quantity'];

            if ($item['is_quantity_unavailable']) {
                $item['quantity'] = $item['stock_quantity'];

                $this->cartModel->updateItem(
                    $cart['id'],
                    $item['id'],
                    $item['quantity']
                );
            }

            $item['item_total'] = $price * $item['quantity'];

            if (!$item['is_out_of_stock']) {
                $subtotal += $item['item_total'];
            }
        }

        unset($item);

        $shippingFee = $subtotal >= getShippingFeeFreeAmount()
            ? 0
            : getShippingFeeStandard();

        // Kiểm tra coupon
        $discount = 0;

        if (isset($_SESSION['coupon'])) {
            $couponModel = new CouponModel();
            $coupon = $couponModel->findById($_SESSION['coupon']['id']);

            if (
                $coupon &&
                $coupon['status'] === 'active' &&
                strtotime($coupon['start_date']) <= time() &&
                strtotime($coupon['end_date']) >= time()
            ) {
                $discountAmt = $couponModel->calculateDiscount(
                    $coupon,
                    $subtotal
                );

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

    /**
     * Thêm sản phẩm vào giỏ hàng.
     * SKU sử dụng giá hoàn chỉnh lưu trong price_adjustment.
     */
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Invalid method'
            ], 405);
            return;
        }

        $productId = filter_var(
            $this->getInput('product_id', 0),
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $this->getInput('quantity', 1),
            FILTER_VALIDATE_INT
        );

        $variantIdRaw = $this->getInput('variant_id', '');

        if (
            $productId === false ||
            $quantity === false ||
            $productId < 1 ||
            $quantity < 1
        ) {
            $this->json([
                'success' => false,
                'message' => 'Sản phẩm hoặc số lượng không hợp lệ'
            ], 400);
            return;
        }

        $variantId = null;

        if ($variantIdRaw !== '' && $variantIdRaw !== null) {
            $variantId = filter_var(
                $variantIdRaw,
                FILTER_VALIDATE_INT
            );

            if ($variantId === false || $variantId < 1) {
                $this->json([
                    'success' => false,
                    'message' => 'Phiên bản sản phẩm không hợp lệ'
                ], 400);
                return;
            }
        }

        $product = $this->productModel->findById($productId);

        if (!$product || $product['status'] !== 'active') {
            $this->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh'
            ], 404);
            return;
        }

        // Lấy danh sách SKU đang hoạt động của sản phẩm
        $skuVariants = $this->productModel->getVariants($productId);

        $hasSkuVariants = !empty($skuVariants);

        $variant = null;
        $variantName = '';
        $stockQuantity = 0;

        if ($hasSkuVariants) {
            // Sản phẩm có SKU thì bắt buộc phải chọn SKU
            if ($variantId === null) {
                $this->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn màu sắc và dung lượng sản phẩm'
                ], 400);
                return;
            }

            // Kiểm tra SKU có thuộc sản phẩm và còn hoạt động không
            $variant = $this->productModel->getVariantById(
                $variantId,
                $productId
            );

            if (!$variant) {
                $this->json([
                    'success' => false,
                    'message' => 'Phiên bản sản phẩm không hợp lệ hoặc đã ngừng kinh doanh'
                ], 400);
                return;
            }

            // price_adjustment hiện được sử dụng làm giá bán hoàn chỉnh
            $finalPrice = (float)$variant['price_adjustment'];

            if ($finalPrice <= 0) {
                $this->json([
                    'success' => false,
                    'message' => 'Phiên bản này chưa được thiết lập giá bán'
                ], 400);
                return;
            }

            $stockQuantity = max(
                0,
                (int)$variant['stock_quantity']
            );

            $variantName = trim(
                $variant['variant_name'] ?? ''
            );

        } else {
            // Sản phẩm không có SKU: sử dụng giá sản phẩm
            if ($variantId !== null) {
                $this->json([
                    'success' => false,
                    'message' => 'Phiên bản sản phẩm không hợp lệ'
                ], 400);
                return;
            }

            $finalPrice = $this->getBaseProductPrice($product);

            $stockQuantity = max(
                0,
                (int)$product['stock_quantity']
            );

            if ($finalPrice <= 0) {
                $this->json([
                    'success' => false,
                    'message' => 'Sản phẩm chưa được thiết lập giá bán'
                ], 400);
                return;
            }
        }

        $cart = $this->getCart();

        // Kiểm tra số lượng đã có trong đúng SKU
        $cartQuantity = $this->cartModel->getProductQuantity(
            $cart['id'],
            $productId,
            $variantId
        );

        if ($cartQuantity + $quantity > $stockQuantity) {
            $this->json([
                'success' => false,
                'message' => 'Sản phẩm không đủ hàng. Tồn kho phiên bản này: ' . $stockQuantity
            ], 400);
            return;
        }

        // Lưu đúng variant_id, không truyền tên phiên bản vào vị trí ID
       $this->cartModel->addItem(
    $cart['id'],
    $productId,
    $quantity,
    $finalPrice,
    $variantId,
    $variantName
);



        $cartCount = $this->cartModel->getItemCount($cart['id']);
        $_SESSION['cart_count'] = $cartCount;

        $variantText = $variantName !== ''
            ? ' (' . $variantName . ')'
            : '';

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

        $itemId = filter_var(
            $this->getInput('item_id', 0),
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $this->getInput('quantity', 1),
            FILTER_VALIDATE_INT
        );

        if (
            $itemId === false ||
            $quantity === false ||
            $itemId < 1 ||
            $quantity < 1
        ) {
            $this->json([
                'success' => false,
                'message' => 'Sản phẩm hoặc số lượng không hợp lệ'
            ], 400);
            return;
        }

        $cart = $this->getCart();

        $cartItem = $this->cartModel->getItemForCart(
            $cart['id'],
            $itemId
        );

        if (!$cartItem || $cartItem['status'] !== 'active') {
            $this->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại trong giỏ hàng'
            ], 404);
            return;
        }

        if ($quantity > max(0, (int)$cartItem['stock_quantity'])) {
            $this->json([
                'success' => false,
                'message' => 'Số lượng vượt quá tồn kho. Tồn kho: ' . (int)$cartItem['stock_quantity']
            ], 400);
            return;
        }

        $this->cartModel->updateItem(
            $cart['id'],
            $itemId,
            $quantity
        );

        // Tính lại tổng tiền theo giá thực tế đã lưu trong từng dòng giỏ hàng
        $items = $this->cartModel->getItems($cart['id']);

        $subtotal = 0;
        $itemTotal = 0;

        foreach ($items as $item) {
            $price = (
                isset($item['price']) &&
                (float)$item['price'] > 0
            )
                ? (float)$item['price']
                : $this->getBaseProductPrice($item);

            $itemSubtotal = $price * (int)$item['quantity'];

            if (
                $item['status'] === 'active' &&
                (int)$item['stock_quantity'] > 0
            ) {
                $subtotal += $itemSubtotal;
            }

            if ((int)$item['id'] === (int)$itemId) {
                $itemTotal = $itemSubtotal;
            }
        }

        $shippingFee = $subtotal >= getShippingFeeFreeAmount()
            ? 0
            : getShippingFeeStandard();

        // Kiểm tra coupon
        $discount = 0;
        $couponCode = '';

        if (isset($_SESSION['coupon'])) {
            $couponModel = new CouponModel();
            $coupon = $couponModel->findById($_SESSION['coupon']['id']);

            if (
                $coupon &&
                $coupon['status'] === 'active' &&
                strtotime($coupon['start_date']) <= time() &&
                strtotime($coupon['end_date']) >= time()
            ) {
                $discountAmt = $couponModel->calculateDiscount(
                    $coupon,
                    $subtotal
                );

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
            'total' => formatPrice(
                $subtotal + $shippingFee - $discount
            ),
            'cartCount' => $cartCount,
            'itemTotal' => formatPrice($itemTotal)
        ]);
    }

    public function remove() {
        $itemId = (int)$this->getInput('item_id', 0);

        $cart = $this->getCart();

        $this->cartModel->removeItem(
            $cart['id'],
            $itemId
        );

        $cartCount = $this->cartModel->getItemCount($cart['id']);
        $_SESSION['cart_count'] = $cartCount;

        setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng');

        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            (
                isset($_SERVER['HTTP_ACCEPT']) &&
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
            )
        ) {
            $this->json([
                'success' => true,
                'cartCount' => $cartCount
            ]);
        } else {
            $this->redirect(url('cart'));
        }
    }

    public function count() {
        $this->json([
            'count' => getCartCount()
        ]);
    }
}
?>
