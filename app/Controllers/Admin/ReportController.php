<?php
class ReportController extends Controller {
    private $orderModel;
    private $productModel;
    private $userModel;
    private $brandModel;

    public function __construct() {
        parent::__construct();
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductModel();
        $this->userModel = new UserModel();
        $this->brandModel = new BrandModel();
    }

    public function index() {
        $this->requireAdmin();
        $period = sanitize($_GET['period'] ?? 'month');
        $year = (int)($_GET['year'] ?? date('Y'));

        $this->view('admin.reports.index', [
            'title' => 'Báo cáo thống kê - Admin',
            'period' => $period,
            'year' => $year,
            'revenueByDay' => $this->orderModel->getRevenueByDay(30),
            'revenueByMonth' => $this->orderModel->getRevenueByMonth($year),
            'revenueByYear' => $this->orderModel->getRevenueByYear(5),
            'topProducts' => $this->productModel->getTopSelling(10),
            'topCustomers' => $this->userModel->getTopCustomers(10),
            'orderStats' => $this->orderModel->getStats(),
            'userStats' => $this->userModel->getStats(),
            'productStats' => $this->productModel->getStats(),
        ]);
    }

    /**
     * Xuất báo cáo đơn hàng XLSX có logo, tiêu đề và định dạng màu xanh lá nhạt.
     * Không thay đổi dữ liệu trong database.
     */
    public function exportOrders() {
        $this->requireAdmin();

        if (!class_exists('ZipArchive')) {
            http_response_code(503);
            echo 'Không thể xuất Excel: PHP ZipArchive chưa được bật trên hosting.';
            return;
        }

        $orders = $this->orderModel->getAll(1, 10000);
        if (!is_array($orders)) {
            $orders = [];
        }

        $headers = [
            'Mã đơn hàng', 'Khách hàng', 'Điện thoại', 'Địa chỉ',
            'Tổng tiền', 'Trạng thái', 'Thanh toán', 'Ngày đặt'
        ];

        $orderStatusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'processing' => 'Đang xử lý',
            'shipping' => 'Đang giao hàng',
            'delivered' => 'Giao thành công',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'return_requested' => 'Yêu cầu trả hàng',
            'returned' => 'Đã hoàn hàng',
            'refunded' => 'Đã hoàn tiền',
        ];

        $paymentStatusLabels = [
            'paid' => 'Đã thanh toán',
            'unpaid' => 'Chưa thanh toán',
            'pending' => 'Chờ thanh toán',
            'refunded' => 'Đã hoàn tiền',
            'failed' => 'Thanh toán thất bại',
            'partial' => 'Thanh toán một phần',
        ];

        $xmlEscape = static function ($value) {
            $value = (string)($value ?? '');
            // Loại bỏ các ký tự điều khiển không hợp lệ trong XML 1.0.
            $value = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/u', '', $value);
            return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };

        $columnNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $sheetRows = [];

        // Dòng 1-4: thông tin cửa hàng và tiêu đề báo cáo.
        $sheetRows[] = '<row r="1" ht="42" customHeight="1">'
            . '<c r="B1" s="1" t="inlineStr"><is><t>VŨ HUÂN SHOP</t></is></c></row>';
        $sheetRows[] = '<row r="2" ht="30" customHeight="1">'
            . '<c r="B2" s="2" t="inlineStr"><is><t>BÁO CÁO ĐƠN HÀNG</t></is></c></row>';
        $sheetRows[] = '<row r="3" ht="8" customHeight="1"/>';
        $sheetRows[] = '<row r="4" ht="24" customHeight="1">'
            . '<c r="A4" s="3" t="inlineStr"><is><t>Ngày xuất báo cáo: '
            . $xmlEscape(date('d/m/Y H:i:s'))
            . '</t></is></c></row>';

        // Dòng 5: tiêu đề cột.
        $headerCells = '';
        foreach ($headers as $index => $header) {
            $ref = $columnNames[$index] . '5';
            $headerCells .= '<c r="' . $ref . '" s="4" t="inlineStr"><is><t>'
                . $xmlEscape($header) . '</t></is></c>';
        }
        $sheetRows[] = '<row r="5" ht="30" customHeight="1">' . $headerCells . '</row>';

        // Dữ liệu bắt đầu từ dòng 6.
        foreach ($orders as $index => $order) {
            if (!is_array($order)) {
                continue;
            }

            $rowNumber = $index + 6;
            $status = (string)($order['status'] ?? '');
            $paymentStatus = (string)($order['payment_status'] ?? '');

            $values = [
                $order['order_number'] ?? '',
                $order['customer_name'] ?? '',
                $order['ship_phone'] ?? '',
                $order['ship_address'] ?? '',
                $order['final_amount'] ?? 0,
                $orderStatusLabels[$status] ?? ($status !== '' ? $status : 'Chưa xác định'),
                $paymentStatusLabels[$paymentStatus] ?? ($paymentStatus !== '' ? $paymentStatus : 'Chưa xác định'),
                $order['created_at'] ?? '',
            ];

            $cells = '';
            $alternate = (($index % 2) === 1);
            foreach ($values as $columnIndex => $value) {
                $ref = $columnNames[$columnIndex] . $rowNumber;

                if ($columnIndex === 4 && is_numeric($value)) {
                    $style = $alternate ? 8 : 7;
                    $cells .= '<c r="' . $ref . '" s="' . $style . '"><v>'
                        . (float)$value . '</v></c>';
                } else {
                    $style = $alternate ? 6 : 5;
                    $cells .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
                        . $xmlEscape($value) . '</t></is></c>';
                }
            }

            $sheetRows[] = '<row r="' . $rowNumber . '" ht="25" customHeight="1">'
                . $cells . '</row>';
        }

        $lastRow = max(5, count($orders) + 5);

        // Tìm logo trong các vị trí thường dùng của dự án.
        $projectRoot = dirname(__DIR__, 3);
        $logoCandidates = [
            $projectRoot . '/public/uploads/logo.png',
            $projectRoot . '/public/uploads/logo.jpg',
            $projectRoot . '/public/uploads/settings/logo.png',
            $projectRoot . '/public/uploads/settings/logo.jpg',
            $projectRoot . '/public/assets/images/logo.png',
            $projectRoot . '/public/assets/images/logo.jpg',
            $projectRoot . '/public/images/logo.png',
            $projectRoot . '/public/images/logo.jpg',
        ];

        $logoPath = null;
        foreach ($logoCandidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                $logoPath = $candidate;
                break;
            }
        }

        $hasLogo = ($logoPath !== null);
        $drawingXml = '';
        $drawingRelsXml = '';
        $sheetDrawingXml = '';
        $contentTypeExtras = '';

        if ($hasLogo) {
            $imageInfo = @getimagesize($logoPath);
            $imageType = $imageInfo['mime'] ?? '';
            $imageExtension = ($imageType === 'image/jpeg') ? 'jpg' : 'png';

            if (!in_array($imageType, ['image/png', 'image/jpeg'], true)) {
                $hasLogo = false;
            } else {
                $drawingXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" '
                    . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
                    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                    . '<xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>50000</xdr:colOff>'
                    . '<xdr:row>0</xdr:row><xdr:rowOff>50000</xdr:rowOff></xdr:from>'
                    . '<xdr:ext cx="850000" cy="850000"/>'
                    . '<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Logo Vu Huan Shop"/>'
                    . '<xdr:cNvPicPr/></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/>'
                    . '<a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
                    . '<xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
                    . '</xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';

                $drawingRelsXml = '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
                    . 'Target="../media/logo.' . $imageExtension . '"/></Relationships>';

                $sheetDrawingXml = '<drawing r:id="rId2"/>';
                $contentTypeExtras = '<Override PartName="/xl/drawings/drawing1.xml" '
                    . 'ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>'
                    . '<Default Extension="' . $imageExtension . '" ContentType="' . $imageType . '"/>';
            }
        }

        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:H' . $lastRow . '"/>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="25"/>'
            . '<cols>'
            . '<col min="1" max="1" width="25" customWidth="1"/>'
            . '<col min="2" max="2" width="25" customWidth="1"/>'
            . '<col min="3" max="3" width="19" customWidth="1"/>'
            . '<col min="4" max="4" width="45" customWidth="1"/>'
            . '<col min="5" max="5" width="22" customWidth="1"/>'
            . '<col min="6" max="7" width="23" customWidth="1"/>'
            . '<col min="8" max="8" width="22" customWidth="1"/>'
            . '</cols><sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '<autoFilter ref="A5:H' . $lastRow . '"/>'
            . $sheetDrawingXml
            . '</worksheet>';

        // Bảng màu xanh lá nhạt: header xanh lá dịu, dòng dữ liệu xen kẽ.
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0 &quot;₫&quot;"/></numFmts>'
            . '<fonts count="5">'
            . '<font><sz val="11"/><name val="Arial"/></font>'
            . '<font><b/><sz val="18"/><color rgb="FF274E13"/><name val="Arial"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF38761D"/><name val="Arial"/></font>'
            . '<font><sz val="10"/><color rgb="FF66805C"/><name val="Arial"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF274E13"/><name val="Arial"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9EAD3"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF4E5"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD5E5CF"/></left>'
            . '<right style="thin"><color rgb="FFD5E5CF"/></right>'
            . '<top style="thin"><color rgb="FFD5E5CF"/></top>'
            . '<bottom style="thin"><color rgb="FFD5E5CF"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="9">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="4" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $contentTypeExtras
            . '</Types>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Đơn hàng" sheetId="1" r:id="rId1"/></sheets></workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $sheetRels = '';
        if ($hasLogo) {
            $sheetRels = '<?xml version="1.0" encoding="UTF-8"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
                . '</Relationships>';
        }

        $files = [
            '[Content_Types].xml' => $contentTypes,
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => $workbook,
            'xl/_rels/workbook.xml.rels' => $workbookRels,
            'xl/worksheets/sheet1.xml' => $worksheet,
            'xl/styles.xml' => $styles,
        ];

        if ($hasLogo) {
            $files['xl/worksheets/_rels/sheet1.xml.rels'] = $sheetRels;
            $files['xl/drawings/drawing1.xml'] = $drawingXml;
            $files['xl/drawings/_rels/drawing1.xml.rels'] = $drawingRelsXml;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'vuhuan-orders-');
        if ($tempFile === false) {
            http_response_code(500);
            echo 'Không thể tạo tệp Excel lúc này.';
            return;
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            @unlink($tempFile);
            http_response_code(500);
            echo 'Không thể khởi tạo tệp Excel. Vui lòng kiểm tra cấu hình ZipArchive trên hosting.';
            return;
        }

        $zipOk = true;
        foreach ($files as $path => $content) {
            if (!$zip->addFromString($path, $content)) {
                $zipOk = false;
                break;
            }
        }

        if ($hasLogo && $zipOk) {
            $imageTarget = 'xl/media/logo.' . $imageExtension;
            if (!$zip->addFile($logoPath, $imageTarget)) {
                $zipOk = false;
            }
        }

        if (!$zipOk || !$zip->close()) {
            @unlink($tempFile);
            http_response_code(500);
            echo 'Không thể hoàn tất tệp Excel. Vui lòng thử lại.';
            return;
        }

        if (!is_file($tempFile) || filesize($tempFile) === 0) {
            @unlink($tempFile);
            http_response_code(500);
            echo 'Tệp Excel được tạo không hợp lệ.';
            return;
        }

        // Xóa mọi output đệm trước khi gửi dữ liệu nhị phân XLSX.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $fileName = 'orders_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');

        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }
}
?>
