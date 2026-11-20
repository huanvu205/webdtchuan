
<?php
require_once __DIR__ . '/../Helpers/Helper.php';

/**
 * ProductModel - Quản lý sản phẩm
 */
class ProductModel
{
    /** @var Database */
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Lấy danh sách biến thể của sản phẩm
     */
    public function getVariants($productId, $activeOnly = true)
    {
        $sql = "SELECT * FROM product_variants
                WHERE product_id = :id";

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY id ASC";

        return $this->db->query($sql)
            ->bind(':id', $productId)
            ->resultSet();
    }

    /**
     * Lấy một biến thể theo ID
     */
    public function getVariantById($variantId, $productId = null)
    {
        $sql = "SELECT * FROM product_variants
                WHERE id = :id AND is_active = 1";

        if ($productId !== null) {
            $sql .= " AND product_id = :product_id";
        }

        $stmt = $this->db->query($sql)
            ->bind(':id', $variantId);

        if ($productId !== null) {
            $stmt->bind(':product_id', $productId);
        }

        return $stmt->single();
    }

    /**
     * Lưu danh sách biến thể sản phẩm.
     *
     * Giá trong price_adjustment được sử dụng làm giá bán
     * hoàn chỉnh của biến thể, không cộng với giá sản phẩm.
     *
     * Tồn kho được quản lý ở cấp sản phẩm:
     * products.stock_quantity
     *
     * Không cộng tồn kho từ các biến thể SKU.
     */
    public function saveVariants($productId, array $rows)
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $sku = trim((string)($row['sku'] ?? ''));
            $name = trim((string)($row['name'] ?? ''));

            // Bỏ qua dòng trống
            if ($sku === '' && $name === '') {
                continue;
            }

            // SKU và tên biến thể đều bắt buộc
            if ($sku === '' || $name === '') {
                return false;
            }

            $attrs = trim((string)($row['attributes_json'] ?? ''));

            // Kiểm tra JSON
            if ($attrs !== '') {
                json_decode($attrs, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return false;
                }
            }

            $variantId = (int)($row['id'] ?? 0);

            // Đây là giá bán hoàn chỉnh của biến thể
            $price = (float)($row['price_adjustment'] ?? 0);

            if ($price < 0) {
                return false;
            }

            if ($variantId > 0) {
                // Kiểm tra biến thể có thuộc sản phẩm hiện tại không
                $existing = $this->db->query("
                    SELECT id
                    FROM product_variants
                    WHERE id = :id AND product_id = :product
                    LIMIT 1
                ")
                    ->bind(':id', $variantId)
                    ->bind(':product', $productId)
                    ->single();

                if (!$existing) {
                    return false;
                }

                $this->db->query("
                    UPDATE product_variants
                    SET sku = :sku,
                        variant_name = :name,
                        attributes_json = :attrs,
                        price_adjustment = :price,
                        is_active = 1,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                      AND product_id = :product
                ")
                    ->bind(':sku', $sku)
                    ->bind(':name', $name)
                    ->bind(':attrs', $attrs !== '' ? $attrs : null)
                    ->bind(':price', $price)
                    ->bind(':id', $variantId)
                    ->bind(':product', $productId)
                    ->execute();

                $keptIds[] = $variantId;
            } else {
                // Không truyền stock_quantity:
                // tồn kho SKU không được quản lý riêng theo README
                $this->db->query("
                    INSERT INTO product_variants (
                        product_id,
                        sku,
                        variant_name,
                        attributes_json,
                        price_adjustment,
                        is_active
                    )
                    VALUES (
                        :product,
                        :sku,
                        :name,
                        :attrs,
                        :price,
                        1
                    )
                ")
                    ->bind(':product', $productId)
                    ->bind(':sku', $sku)
                    ->bind(':name', $name)
                    ->bind(':attrs', $attrs !== '' ? $attrs : null)
                    ->bind(':price', $price)
                    ->execute();

                $newId = (int)$this->db->lastInsertId();

                if ($newId > 0) {
                    $keptIds[] = $newId;
                }
            }
        }

        // Chỉ giữ lại những biến thể có trong danh sách gửi lên
        if (!empty($keptIds)) {
            $ids = implode(',', array_map('intval', $keptIds));

            $this->db->query("
                UPDATE product_variants
                SET is_active = 0,
                    updated_at = CURRENT_TIMESTAMP
                WHERE product_id = :product
                  AND id NOT IN ($ids)
            ")
                ->bind(':product', $productId)
                ->execute();
        } else {
            // Không có biến thể nào được gửi lên
            $this->db->query("
                UPDATE product_variants
                SET is_active = 0,
                    updated_at = CURRENT_TIMESTAMP
                WHERE product_id = :product
            ")
                ->bind(':product', $productId)
                ->execute();
        }

        // Không cập nhật tồn kho sản phẩm từ tồn kho SKU.
        // Tồn kho được quản lý tại products.stock_quantity.

        return true;
    }

    /**
     * Lấy danh sách sản phẩm với bộ lọc
     */
    public function getList($filters = [], $page = 1, $limit = 12, $sort = 'newest')
    {
        $offset = ($page - 1) * $limit;

        $where = ["p.status = 'active'"];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['brand_id'])) {
            $where[] = "p.brand_id = :brand_id";
            $params[':brand_id'] = $filters['brand_id'];
        }

        if (!empty($filters['min_price'])) {
            $where[] = "COALESCE(p.price_discount, p.price_sale) >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $where[] = "COALESCE(p.price_discount, p.price_sale) <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(
                p.name LIKE :search1
                OR p.product_code LIKE :search2
                OR b.name LIKE :search3
            )";

            $sVal = '%' . trim($filters['search']) . '%';

            $params[':search1'] = $sVal;
            $params[':search2'] = $sVal;
            $params[':search3'] = $sVal;
        }

        $whereStr = "WHERE " . implode(" AND ", $where);

        switch ($sort) {
            case 'price_asc':
                $orderBy = 'COALESCE(p.price_discount, p.price_sale) ASC';
                break;

            case 'price_desc':
                $orderBy = 'COALESCE(p.price_discount, p.price_sale) DESC';
                break;

            case 'best_selling':
                $orderBy = 'p.view_count DESC';
                break;

            case 'rating':
                $orderBy = 'p.rating_avg DESC';
                break;

            default:
                $orderBy = 'p.created_at DESC';
                break;
        }

        $sql = "
            SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug,
                b.name AS brand_name,
                b.slug AS brand_slug,
                b.logo AS brand_logo
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            $whereStr
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->query($sql)
            ->bind(':limit', $limit)
            ->bind(':offset', $offset);

        foreach ($params as $key => $value) {
            $stmt->bind($key, $value);
        }

        return $stmt->resultSet();
    }

    /**
     * Đếm sản phẩm theo bộ lọc
     */
    public function countList($filters = [])
    {
        $where = ["p.status = 'active'"];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['brand_id'])) {
            $where[] = "p.brand_id = :brand_id";
            $params[':brand_id'] = $filters['brand_id'];
        }

        if (!empty($filters['min_price'])) {
            $where[] = "COALESCE(p.price_discount, p.price_sale) >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $where[] = "COALESCE(p.price_discount, p.price_sale) <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(
                p.name LIKE :search1
                OR p.product_code LIKE :search2
                OR b.name LIKE :search3
            )";

            $sVal = '%' . trim($filters['search']) . '%';

            $params[':search1'] = $sVal;
            $params[':search2'] = $sVal;
            $params[':search3'] = $sVal;
        }

        $whereStr = "WHERE " . implode(" AND ", $where);

        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            $whereStr
        ");

        foreach ($params as $key => $value) {
            $stmt->bind($key, $value);
        }

        $result = $stmt->single();

        return $result['total'] ?? 0;
    }

    /**
     * Tìm sản phẩm theo ID
     */
    public function findById($id)
    {
        return $this->db->query("
            SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug,
                b.name AS brand_name,
                b.slug AS brand_slug,
                b.logo AS brand_logo
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.id = :id
        ")
            ->bind(':id', $id)
            ->single();
    }

    /**
     * Tìm sản phẩm theo slug
     */
    public function findBySlug($slug)
    {
        return $this->db->query("
            SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug,
                b.name AS brand_name,
                b.slug AS brand_slug,
                b.logo AS brand_logo
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.slug = :slug
              AND p.status = 'active'
        ")
            ->bind(':slug', $slug)
            ->single();
    }

    /**
     * Lấy sản phẩm nổi bật
     */
    public function getFeatured($limit = 8)
    {
        return $this->db->query("
            SELECT p.*, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.status = 'active'
              AND p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT :limit
        ")
            ->bind(':limit', $limit)
            ->resultSet();
    }

    /**
     * Lấy sản phẩm mới
     */
    public function getNew($limit = 8)
    {
        return $this->db->query("
            SELECT p.*, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.status = 'active'
            ORDER BY p.is_newest DESC, p.created_at DESC
            LIMIT :limit
        ")
            ->bind(':limit', $limit)
            ->resultSet();
    }

    /**
     * Lấy sản phẩm bán chạy
     */
    public function getBestSelling($limit = 8)
    {
        return $this->db->query("
            SELECT
                p.*,
                b.name AS brand_name,
                (
                    SELECT COALESCE(SUM(od.quantity), 0)
                    FROM order_details od
                    WHERE od.product_id = p.id
                ) AS total_sold
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.status = 'active'
            ORDER BY p.is_best_selling DESC, total_sold DESC
            LIMIT :limit
        ")
            ->bind(':limit', $limit)
            ->resultSet();
    }

    /**
     * Lấy sản phẩm khuyến mãi
     */
    public function getHotDeal($limit = 8)
    {
        return $this->db->query("
            SELECT p.*, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.status = 'active'
              AND p.is_hot_deal = 1
              AND p.price_discount IS NOT NULL
            ORDER BY p.discount_percent DESC
            LIMIT :limit
        ")
            ->bind(':limit', $limit)
            ->resultSet();
    }

    /**
     * Lấy sản phẩm liên quan
     */
    public function getRelated($productId, $categoryId, $limit = 6)
    {
        return $this->db->query("
            SELECT p.*, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.status = 'active'
              AND p.category_id = :cat_id
              AND p.id != :id
            ORDER BY RAND()
            LIMIT :limit
        ")
            ->bind(':cat_id', $categoryId)
            ->bind(':id', $productId)
            ->bind(':limit', $limit)
            ->resultSet();
    }

    /**
     * Tìm kiếm sản phẩm
     */
    public function search($keyword, $limit = 10)
    {
        $limit = max(1, (int)$limit);

        $cleanKw = trim($keyword);
        $kw = '%' . $cleanKw . '%';

        $sql = "
            SELECT p.*, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.status = 'active'
              AND (
                  p.name LIKE :kw1
                  OR p.product_code LIKE :kw2
                  OR b.name LIKE :kw3
              )
            ORDER BY p.rating_avg DESC
            LIMIT {$limit}
        ";

        return $this->db->query($sql)
            ->bind(':kw1', $kw)
            ->bind(':kw2', $kw)
            ->bind(':kw3', $kw)
            ->resultSet();
    }

    /**
     * Tăng lượt xem sản phẩm
     */
    public function incrementView($id)
    {
        $this->db->query("
            UPDATE products
            SET view_count = view_count + 1
            WHERE id = :id
        ")
            ->bind(':id', $id)
            ->execute();
    }

    /**
     * Lấy ảnh sản phẩm
     */
    public function getImages($productId)
    {
        return $this->db->query("
            SELECT *
            FROM product_images
            WHERE product_id = :id
            ORDER BY display_order ASC, is_main DESC
        ")
            ->bind(':id', $productId)
            ->resultSet();
    }

    /**
     * Lấy thông số kỹ thuật
     */
    public function getSpecs($productId)
    {
        return $this->db->query("
            SELECT *
            FROM product_specs
            WHERE product_id = :id
            ORDER BY display_order ASC
        ")
            ->bind(':id', $productId)
            ->resultSet();
    }

    /**
     * Tạo sản phẩm
     */
    public function create($data)
    {
        $slug = createSlug($data['name']);

        // Tạo slug duy nhất
        $existing = $this->findBySlug($slug);

        if ($existing) {
            $slug .= '-' . time();
        }

        $this->db->query("
            INSERT INTO products (
                product_code,
                name,
                slug,
                category_id,
                brand_id,
                description,
                short_description,
                price_cost,
                price_sale,
                price_discount,
                discount_percent,
                main_image,
                status,
                stock_quantity,
                is_featured,
                is_hot_deal,
                is_best_selling,
                is_newest,
                variants_json
            )
            VALUES (
                :product_code,
                :name,
                :slug,
                :category_id,
                :brand_id,
                :description,
                :short_description,
                :price_cost,
                :price_sale,
                :price_discount,
                :discount_percent,
                :main_image,
                :status,
                :stock_quantity,
                :is_featured,
                :is_hot_deal,
                :is_best_selling,
                :is_newest,
                :variants_json
            )
        ")
            ->bind(':product_code', $data['product_code'])
            ->bind(':name', $data['name'])
            ->bind(':slug', $slug)
            ->bind(':category_id', $data['category_id'])
            ->bind(':brand_id', $data['brand_id'])
            ->bind(':description', $data['description'] ?? null)
            ->bind(':short_description', $data['short_description'] ?? null)
            ->bind(':price_cost', $data['price_cost'])
            ->bind(':price_sale', $data['price_sale'])
            ->bind(':price_discount', $data['price_discount'] ?? null)
            ->bind(':discount_percent', $data['discount_percent'] ?? 0)
            ->bind(':main_image', $data['main_image'] ?? null)
            ->bind(':status', $data['status'] ?? 'active')
            ->bind(':stock_quantity', $data['stock_quantity'] ?? 0)
            ->bind(':is_featured', $data['is_featured'] ?? 0)
            ->bind(':is_hot_deal', $data['is_hot_deal'] ?? 0)
            ->bind(':is_best_selling', $data['is_best_selling'] ?? 0)
            ->bind(':is_newest', $data['is_newest'] ?? 0)
            ->bind(':variants_json', $data['variants_json'] ?? null)
            ->execute();

        $productId = $this->db->lastInsertId();

        // Thêm tồn kho cấp sản phẩm vào inventory
        $this->db->query("
            INSERT INTO inventory (product_id, quantity)
            VALUES (:product_id, :quantity)
        ")
            ->bind(':product_id', $productId)
            ->bind(':quantity', $data['stock_quantity'] ?? 0)
            ->execute();

        return $productId;
    }

    /**
     * Cập nhật sản phẩm
     */
    public function update($id, $data)
    {
        $sql = "
            UPDATE products SET
                product_code = :product_code,
                name = :name,
                category_id = :category_id,
                brand_id = :brand_id,
                description = :description,
                short_description = :short_description,
                price_cost = :price_cost,
                price_sale = :price_sale,
                price_discount = :price_discount,
                discount_percent = :discount_percent,
                status = :status,
                stock_quantity = :stock_quantity,
                is_featured = :is_featured,
                is_hot_deal = :is_hot_deal,
                is_best_selling = :is_best_selling,
                is_newest = :is_newest,
                variants_json = :variants_json
        ";

        if (!empty($data['main_image'])) {
            $sql .= ", main_image = :main_image";
        }

        $stmt = $this->db->query($sql . " WHERE id = :id")
            ->bind(':product_code', $data['product_code'])
            ->bind(':name', $data['name'])
            ->bind(':category_id', $data['category_id'])
            ->bind(':brand_id', $data['brand_id'])
            ->bind(':description', $data['description'] ?? null)
            ->bind(':short_description', $data['short_description'] ?? null)
            ->bind(':price_cost', $data['price_cost'])
            ->bind(':price_sale', $data['price_sale'])
            ->bind(':price_discount', $data['price_discount'] ?? null)
            ->bind(':discount_percent', $data['discount_percent'] ?? 0)
            ->bind(':status', $data['status'] ?? 'active')
            ->bind(':stock_quantity', $data['stock_quantity'] ?? 0)
            ->bind(':is_featured', $data['is_featured'] ?? 0)
            ->bind(':is_hot_deal', $data['is_hot_deal'] ?? 0)
            ->bind(':is_best_selling', $data['is_best_selling'] ?? 0)
            ->bind(':is_newest', $data['is_newest'] ?? 0)
            ->bind(':variants_json', $data['variants_json'] ?? null)
            ->bind(':id', $id);

        if (!empty($data['main_image'])) {
            $stmt->bind(':main_image', $data['main_image']);
        }

        if (!$stmt->execute()) {
            return false;
        }

        // Đồng bộ tồn kho cấp sản phẩm với bảng inventory
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $this->db->query("
                INSERT INTO inventory (product_id, quantity)
                VALUES (:product_id, :quantity)
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
            ")
                ->bind(':product_id', $id)
                ->bind(':quantity', $data['stock_quantity'] ?? 0)
                ->execute();
        }

        return true;
    }

    /**
     * Xóa mềm sản phẩm
     */
    public function delete($id)
    {
        return $this->db->query("
            UPDATE products
            SET status = 'discontinued'
            WHERE id = :id
        ")
            ->bind(':id', $id)
            ->execute();
    }

    /**
     * Thêm ảnh sản phẩm
     */
    public function addImage($productId, $imagePath, $isMain = 0)
    {
        return $this->db->query("
            INSERT INTO product_images (
                product_id,
                image_path,
                is_main
            )
            VALUES (
                :product_id,
                :image_path,
                :is_main
            )
        ")
            ->bind(':product_id', $productId)
            ->bind(':image_path', $imagePath)
            ->bind(':is_main', $isMain)
            ->execute();
    }

    /**
     * Xóa ảnh sản phẩm
     */
    public function deleteImage($imageId)
    {
        return $this->db->query("
            DELETE FROM product_images
            WHERE id = :id
        ")
            ->bind(':id', $imageId)
            ->execute();
    }

    /**
     * Thêm thông số kỹ thuật
     */
    public function addSpec($productId, $specName, $specValue, $order = 0)
    {
        return $this->db->query("
            INSERT INTO product_specs (
                product_id,
                spec_name,
                spec_value,
                display_order
            )
            VALUES (
                :product_id,
                :spec_name,
                :spec_value,
                :display_order
            )
        ")
            ->bind(':product_id', $productId)
            ->bind(':spec_name', $specName)
            ->bind(':spec_value', $specValue)
            ->bind(':display_order', $order)
            ->execute();
    }

    /**
     * Xóa thông số kỹ thuật của sản phẩm
     */
    public function deleteSpecs($productId)
    {
        return $this->db->query("
            DELETE FROM product_specs
            WHERE product_id = :id
        ")
            ->bind(':id', $productId)
            ->execute();
    }

    /**
     * Lấy danh sách sản phẩm trong trang quản trị
     */
    public function getAllAdmin(
        $page = 1,
        $limit = 20,
        $search = '',
        $category = '',
        $brand = '',
        $status = ''
    ) {
        $offset = ($page - 1) * $limit;

        $where = ["1=1"];
        $params = [];

        if ($search) {
            $where[] = "(p.name LIKE :search OR p.product_code LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($category) {
            $where[] = "p.category_id = :category_id";
            $params[':category_id'] = $category;
        }

        if ($brand) {
            $where[] = "p.brand_id = :brand_id";
            $params[':brand_id'] = $brand;
        }

        if ($status) {
            $where[] = "p.status = :status";
            $params[':status'] = $status;
        }

        $whereStr = "WHERE " . implode(" AND ", $where);

        $stmt = $this->db->query("
            SELECT
                p.*,
                c.name AS category_name,
                b.name AS brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            $whereStr
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ")
            ->bind(':limit', $limit)
            ->bind(':offset', $offset);

        foreach ($params as $key => $value) {
            $stmt->bind($key, $value);
        }

        return $stmt->resultSet();
    }

    /**
     * Đếm sản phẩm trong trang quản trị
     */
    public function countAdmin($search = '', $category = '', $brand = '', $status = '')
    {
        $where = ["1=1"];
        $params = [];

        if ($search) {
            $where[] = "(p.name LIKE :search OR p.product_code LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($category) {
            $where[] = "p.category_id = :category_id";
            $params[':category_id'] = $category;
        }

        if ($brand) {
            $where[] = "p.brand_id = :brand_id";
            $params[':brand_id'] = $brand;
        }

        if ($status) {
            $where[] = "p.status = :status";
            $params[':status'] = $status;
        }

        $whereStr = "WHERE " . implode(" AND ", $where);

        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM products p
            $whereStr
        ");

        foreach ($params as $key => $value) {
            $stmt->bind($key, $value);
        }

        $result = $stmt->single();

        return $result['total'] ?? 0;
    }

    /**
     * Thống kê sản phẩm
     */
    public function getStats()
    {
        return $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN stock_quantity < 10 THEN 1 ELSE 0 END) AS low_stock,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock
            FROM products
        ")->single();
    }

    /**
     * Lấy danh sách sản phẩm bán chạy nhất
     */
    public function getTopSelling($limit = 10)
    {
        $limitSql = (defined('DB_TYPE') && DB_TYPE === 'mysql')
            ? 'LIMIT :limit'
            : 'OFFSET 0 ROWS FETCH NEXT :limit ROWS ONLY';

        return $this->db->query("
            SELECT
                p.*,
                sales.total_sold,
                sales.total_revenue
            FROM products p
            INNER JOIN (
                SELECT
                    od.product_id,
                    SUM(od.quantity) AS total_sold,
                    SUM(od.subtotal) AS total_revenue
                FROM order_details od
                INNER JOIN orders o ON o.id = od.order_id
                WHERE o.status = 'delivered'
                   OR o.payment_status = 'paid'
                GROUP BY od.product_id
                HAVING SUM(od.quantity) > 0
            ) sales ON sales.product_id = p.id
            ORDER BY sales.total_sold DESC, sales.total_revenue DESC
            $limitSql
        ")
            ->bind(':limit', max(1, (int)$limit))
            ->resultSet();
    }

    /**
     * Cập nhật tồn kho sản phẩm
     */
    public function updateStock($id, $quantity)
    {
        return $this->db->query("
            UPDATE products
            SET stock_quantity = :quantity
            WHERE id = :id
        ")
            ->bind(':quantity', $quantity)
            ->bind(':id', $id)
            ->execute();
    }

    /**
     * Lấy danh sách sản phẩm sắp hết hàng
     */
    public function getLowStock($threshold = 10)
    {
        return $this->db->query("
            SELECT p.*, b.name AS brand_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.status = 'active'
              AND p.stock_quantity <= :threshold
            ORDER BY p.stock_quantity ASC
        ")
            ->bind(':threshold', $threshold)
            ->resultSet();
    }

    /**
     * Lấy sản phẩm active cho sitemap và catalog
     */
    public function getActive($limit = 100)
    {
        return $this->getList([], 1, $limit);
    }
}
?>
