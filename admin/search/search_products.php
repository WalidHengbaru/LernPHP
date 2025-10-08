<form method="GET" class="mb-4 flex gap-2">
    <input type="hidden" name="section" value="products">
    <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
    <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 rounded ">
        <div class="bg-blue-500 py-2 text-center text-white font-semibold text-[16px] rounded-t shadow">
            ค้นหาสินค้า
        </div>
        <div class="flex items-center gap-x-5 w-full mt-3">
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-end">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">ชื่อสินค้า :</span>
                <input 
                    type="text" 
                    name="product_name" 
                    id="search_product_name" 
                    value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                    class="!text-[14px] focus:outline-none focus:ring-1 focus:ring-blue-500/60 focus:border-blue-500/60 transition-colors duration-200 cursor-pointer py-[0.3rem] px-1 border !border-gray-200 rounded text-left w-[240px]">
            </div>
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-start">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">หมวดหมู่ :</span>
                <input 
                    type="text" 
                    name="category" 
                    id="search_category" 
                    value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                    class="!text-[14px] focus:outline-none focus:ring-1 focus:ring-blue-500/60 focus:border-blue-500/60 transition-colors duration-200 cursor-pointer py-[0.3rem] px-1 border !border-gray-200 rounded text-left w-[240px]">
            </div>
        </div>
        <div class="flex items-center justify-center gap-x-4 w-full my-5">
            <input 
                type="submit" 
                value="ค้นหาข้อมูล" 
                class="bg-green-500 rounded text-white w-[120px] py-2 font-semibold text-[14px] text-center hover:cursor-pointer hover:bg-green-600 hover:shadow"
            >
            <a 
                href="admin_index.php?section=products&tab_id=<?php echo urlencode($tab_id); ?>"
                class="bg-blue-500 rounded text-white w-[120px] py-2 font-semibold text-[14px] text-center hover:cursor-pointer hover:bg-blue-600 hover:shadow"
                >ค้นหาทั้งหมด
            </a>
        </div>
    </div>
</form>
<div class="flex justify-between text-[14px] text-green-700 font-bold mt-4 mb-2">
    <div>พบสินค้า <span class="text-red-600"><?php echo count($products); ?></span> รายการ</div>
</div>
<div class="w-full mt-2 mx-auto overflow-x-auto">
    <table class="w-full border border-separate border-spacing-0 border-gray-300 rounded-t text-center">
        <thead>
            <tr>
                <td colspan="8" class="bg-blue-500 py-2 text-center text-white text-[16px] rounded-t">
                    รายการสินค้า
                </td>
            </tr>
            <tr class="w-full bg-blue-600 grid grid-cols-[120px_120px_1fr_130px_90px_90px_90px_90px] text-white text-center">
                <td class="px-1 py-2 border-r border-gray-300">รูปภาพ</td>
                <td class="px-1 py-2 border-r border-gray-300">ชื่อ</td>
                <td class="px-1 py-2 border-r border-gray-300">คำอธิบาย</td>
                <td class="px-1 py-2 border-r border-gray-300">ราคา</td>
                <td class="px-1 py-2 border-r border-gray-300">สต็อก</td>
                <td class="px-1 py-2 border-r border-gray-300">แก้ไข</td>
                <td class="px-1 py-2 border-r border-gray-300">ลบ</td>
                <td class="px-1 py-2">สถานะสินค้า</td>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="8" class="px-1 py-2 text-center text-gray-500">ไม่มีข้อมูลสินค้า</td></tr>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <tr class="w-full grid grid-cols-[120px_120px_1fr_130px_90px_90px_90px_90px] text-center">
                        <td class="px-1 py-2 border-r border-gray-300">
                            <img src="<?php echo htmlspecialchars($p['product_image'] ?: '../assets/Uploads/default.png'); ?>" 
                                 alt="Product Image" 
                                 class="w-16 h-16 object-cover rounded mx-auto">
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php echo htmlspecialchars($p['name'] ?? 'N/A'); ?>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300 text-left">
                            <?php 
                                // แสดงคำอธิบายแบบตัดสั้น ไม่เอาแท็ก HTML
                                $shortDesc = mb_substr(strip_tags($p['description'] ?? ''), 0, 50);
                                echo $shortDesc ?: 'N/A';
                            ?>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php echo number_format($p['price'] ?? 0, 2); ?> บาท
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php echo htmlspecialchars($p['stock'] ?? 0); ?>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <a href="edit_product.php?id=<?php echo $p['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" 
                            class="text-blue-500 hover:underline">แก้ไข</a>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <a href="admin_edit_handler.php?action=delete_product&id=<?php echo $p['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" 
                            class="text-red-500 hover:underline ml-2" 
                            onclick="return confirm('ยืนยันการลบสินค้า?')">ลบ</a>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300 relative text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    class="sr-only peer"
                                    onchange="toggleProductStatus(<?php echo $p['id']; ?>)" 
                                    <?php echo $p['active'] ? 'checked' : ''; ?>
                                >
                                <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-green-500 
                                    peer-focus:ring-2 peer-focus:ring-green-300 
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px] 
                                    after:bg-white after:border-gray-300 after:border after:rounded-full 
                                    after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
function toggleProductStatus(productId) {
    fetch('admin_edit_handler.php?action=toggle_product_status&id=' + productId + '&tab_id=' + new URLSearchParams(window.location.search).get('tab_id'), {
        method: 'GET'
    }).then(() => {
        location.reload(); // รีเฟรชหน้าเพื่อให้แสดงสถานะล่าสุด
    });
}
</script>

