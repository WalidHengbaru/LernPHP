<form method="GET" class="mb-4 flex gap-2">
    <input type="hidden" name="section" value="reviews">
    <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
    <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 rounded ">
        <div class="bg-blue-500 py-2 text-center text-white font-semibold text-[16px] rounded-t shadow">
            ค้นหารีวิว
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
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">ชื่อผู้ใช้ :</span>
                <input 
                    type="text" 
                    name="username" 
                    id="search_username" 
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
                href="admin_index.php?section=reviews&tab_id=<?php echo urlencode($tab_id); ?>"
                class="bg-blue-500 rounded text-white w-[120px] py-2 font-semibold text-[14px] text-center hover:cursor-pointer hover:bg-blue-600 hover:shadow"
                >ค้นหาทั้งหมด
            </a>
        </div>
    </div>
</form>
<div class="flex justify-between text-[14px] text-green-700 font-bold mt-4 mb-2">
    <div>พบรีวิว <span class="text-red-600"><?php echo count($reviews); ?></span> รายการ</div>
</div>
<div class="w-full mt-2 mx-auto overflow-x-auto">
    <table class="w-full border border-separate border-spacing-0 border-gray-300 rounded-t text-center">
        <thead>
            <tr>
                <td colspan="6" class="bg-blue-500 py-2 text-center text-white text-[16px] rounded-t">
                    รายการรีวิวสินค้า
                </td>
            </tr>
            <tr class="w-full bg-blue-600 grid grid-cols-[120px_150px_120px_1fr_100px_100px] text-white text-center">
                <td class="px-1 py-2 border-r border-gray-300">สินค้า</td>
                <td class="px-1 py-2 border-r border-gray-300">ชื่อผู้ใช้</td>
                <td class="px-1 py-2 border-r border-gray-300">คะแนน</td>
                <td class="px-1 py-2 border-r border-gray-300">ความคิดเห็น</td>
                <td class="px-1 py-2 border-r border-gray-300">วันที่</td>
                <td class="px-1 py-2">การจัดการ</td>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr><td colspan="6" class="px-1 py-2 text-center text-gray-500">ไม่มีข้อมูลรีวิว</td></tr>
            <?php else: ?>
                <?php foreach ($reviews as $r): ?>
                    <tr class="w-full grid grid-cols-[120px_150px_120px_1fr_100px_100px] text-center">
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($r['product_name'] ?? 'N/A'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($r['username'] ?? 'N/A'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($r['rating'] ?? 'N/A'); ?> ดาว</td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($r['comment'] ?? 'ไม่มีความคิดเห็น'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($r['created_at'] ?? 'N/A'); ?></td>
                        <td class="px-1 py-2">
                            <a href="admin_edit_handler.php?action=delete_review&id=<?php echo $r['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" class="text-red-500 hover:underline" onclick="return confirm('ยืนยันการลบรีวิว?')">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>