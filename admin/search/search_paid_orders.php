<form method="GET" class="mb-4 flex gap-2">
    <input type="hidden" name="section" value="paid_orders">
    <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
    <div class="flex flex-col w-full bg-gray-100/60 border border-gray-200 rounded">
        <div class="bg-blue-500 py-2 text-center text-white font-semibold text-[16px] rounded-t shadow">
            ค้นหาคำสั่งซื้อที่ชำระเงินแล้ว
        </div>
        <div class="flex items-center gap-x-5 w-full mt-3">
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-end">
                <span class="w-[120px] font-semibold text-[14px] text-gray-600 text-right">ชื่อผู้ใช้ :</span>
                <input 
                    type="text" 
                    name="username" 
                    value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>" 
                    class="text-[14px] border border-gray-300 rounded px-2 py-[0.3rem] w-[240px] focus:ring-1 focus:ring-blue-500/60">
            </div>
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-start">
                <span class="w-[120px] font-semibold text-[14px] text-gray-600 text-right">ชื่อสินค้า :</span>
                <input 
                    type="text" 
                    name="product_name" 
                    value="<?php echo htmlspecialchars($_GET['product_name'] ?? ''); ?>" 
                    class="text-[14px] border border-gray-300 rounded px-2 py-[0.3rem] w-[240px] focus:ring-1 focus:ring-blue-500/60">
            </div>
        </div>
        <div class="flex items-center justify-center gap-x-4 w-full my-5">
            <input 
                type="submit" 
                value="ค้นหาข้อมูล" 
                class="bg-green-500 rounded text-white w-[120px] py-2 font-semibold text-[14px] hover:bg-green-600 shadow"
            >
            <a 
                href="admin_index.php?section=paid_orders&tab_id=<?php echo urlencode($tab_id); ?>"
                class="bg-blue-500 rounded text-white w-[120px] py-2 font-semibold text-[14px] text-center hover:bg-blue-600 shadow"
                >ค้นหาทั้งหมด
            </a>
        </div>
    </div>
</form>

<div class="flex justify-between text-[14px] text-green-700 font-bold mt-4 mb-2">
    <div>พบคำสั่งซื้อ <span class="text-red-600"><?php echo count($paid_orders); ?></span> รายการ</div>
</div>

<div class="w-full mt-2 mx-auto overflow-x-auto">
    <table class="w-full border border-separate border-spacing-0 border-gray-300 rounded-t text-center">
        <thead>
            <tr>
                <td colspan="9" class="bg-blue-500 py-2 text-center text-white text-[16px] rounded-t">
                    คำสั่งซื้อที่ชำระเงินแล้ว
                </td>
            </tr>
            <tr class="bg-blue-600 text-white text-center">
                <th class="px-2 py-2 border-r border-gray-300">ชื่อผู้ใช้</th>
                <th class="px-2 py-2 border-r border-gray-300">รูปสินค้า</th>
                <th class="px-2 py-2 border-r border-gray-300">ชื่อสินค้า</th>
                <th class="px-2 py-2 border-r border-gray-300">จำนวน</th>
                <th class="px-2 py-2 border-r border-gray-300">ราคารวม</th>
                <th class="px-2 py-2 border-r border-gray-300">วิธีชำระ</th>
                <th class="px-2 py-2 border-r border-gray-300">ยอดชำระ</th>
                <th class="px-2 py-2 border-r border-gray-300">วันที่ชำระ</th>
                <th class="px-2 py-2">การจัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($paid_orders)): ?>
                <tr><td colspan="9" class="px-1 py-4 text-center text-gray-500">ไม่มีข้อมูลคำสั่งซื้อ</td></tr>
            <?php else: ?>
                <?php foreach ($paid_orders as $order): ?>
                    <tr class="text-center bg-white border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($order['username']); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <img src="<?php echo htmlspecialchars($order['product_image'] ?? getDefaultImage()); ?>" 
                                 alt="Product" class="w-16 h-16 object-cover rounded mx-auto">
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo (int)$order['quantity']; ?></td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php echo number_format($order['quantity'] * $order['price'], 2); ?> บาท
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php 
                                echo htmlspecialchars(
                                    $order['payment_method'] === 'cod' ? 'เงินปลายทาง' :
                                    ($order['payment_method'] === 'qrcode' ? 'QR Code' : 'บัตรเครดิต/เดบิต')
                                ); 
                            ?>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300 text-green-700 font-semibold">
                            <?php echo number_format($order['amount'], 2); ?> บาท
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($order['created_at']); ?></td>
                        <td class="px-1 py-2">
                            <a href="view_payment.php?order_id=<?php echo urlencode($order['order_id']); ?>&tab_id=<?php echo urlencode($tab_id); ?>" 
                               class="text-blue-600 hover:underline">ดูข้อมูล</a>
                            <?php if ($_SESSION['admin_level'] === 'super_admin'): ?>
                                <a href="admin_edit_handler.php?action=delete_order&id=<?php echo $order['order_id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" 
                                   class="text-red-500 hover:underline ml-2"
                                   onclick="return confirm('ยืนยันการลบคำสั่งซื้อ?')">ลบ</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>