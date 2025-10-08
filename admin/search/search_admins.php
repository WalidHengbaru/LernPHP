<form method="GET" class="mb-4 flex gap-2">
    <input type="hidden" name="section" value="admins">
    <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
    <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 rounded ">
        <div class="bg-blue-500 py-2 text-center text-white font-semibold text-[16px] rounded-t shadow">
            ค้นหาแอดมิน
        </div>
        <div class="flex items-center gap-x-5 w-full mt-3">
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-end">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">ชื่อผู้ใช้ :</span>
                <input 
                    type="text" 
                    name="username" 
                    id="search_username" 
                    value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                    class="!text-[14px] focus:outline-none focus:ring-1 focus:ring-blue-500/60 focus:border-blue-500/60 transition-colors duration-200 cursor-pointer py-[0.3rem] px-1 border !border-gray-200 rounded text-left w-[240px]">
            </div>
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-start">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">อีเมล :</span>
                <input 
                    type="text" 
                    name="email" 
                    id="search_email" 
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
                href="admin_index.php?section=admins&tab_id=<?php echo urlencode($tab_id); ?>"
                class="bg-blue-500 rounded text-white w-[120px] py-2 font-semibold text-[14px] text-center hover:cursor-pointer hover:bg-blue-600 hover:shadow"
                >ค้นหาทั้งหมด
            </a>
        </div>
    </div>
</form>
<div class="flex justify-between text-[14px] text-green-700 font-bold mt-4 mb-2">
    <div>พบแอดมิน <span class="text-red-600"><?php echo count($admins); ?></span> รายการ</div>
</div>
<div class="w-full mt-2 mx-auto overflow-x-auto">
    <table class="w-full border border-separate border-spacing-0 border-gray-300 rounded-t text-center">
        <thead>
            <tr>
                <th colspan="5" class="bg-blue-500 py-2 text-center text-white text-[16px] rounded-t">
                    รายการแอดมิน
                </th>
            </tr>
            <tr class="w-full bg-blue-600 grid grid-cols-[150px_1fr_180px_100px_100px] text-white text-center">
                <td class="px-1 py-2 border-r border-gray-300">ชื่อผู้ใช้</td>
                <td class="px-1 py-2 border-r border-gray-300">อีเมล</td>
                <td class="px-1 py-2 border-r border-gray-300">ระดับ</td>
                <td class="px-1 py-2 border-r border-gray-300">การจัดการ</td>
                <td class="px-1 py-2">การจัดการ</td>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($admins)): ?>
                <tr><td colspan="5" class="px-1 py-2 text-center text-gray-500">ไม่มีข้อมูลแอดมิน</td></tr>
            <?php else: ?>
                <?php foreach ($admins as $a): ?>
                    <tr class="w-full grid grid-cols-[150px_1fr_180px_100px_100px] text-center">
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php echo htmlspecialchars($a['username'] ?? 'N/A'); ?>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php echo htmlspecialchars($a['email'] ?? 'N/A'); ?>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php echo $a['admin_level'] === 'super_admin' ? 'แอดมินใหญ่' : 'แอดมินรอง'; ?>
                        </td>

                        <?php if ($_SESSION['admin_level'] === 'regular_admin' && $a['admin_level'] === 'super_admin'): ?>
                            <!-- regular_admin เห็น super_admin -->
                            <td colspan="2" class="px-1 py-2 text-gray-400 italic border-r border-gray-300">
                                ระดับไม่เพียงพอ
                            </td>
                        <?php else: ?>
                            <!-- super_admin หรือ regular_admin เห็นคนระดับเท่ากัน/ต่ำกว่า -->
                            <td class="px-1 py-2 border-r border-gray-300">
                                <a href="/LearnPHP/admin/edit_admin.php?id=<?php echo $a['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>"
                                    class="text-blue-500 hover:underline">แก้ไข</a>
                            </td>
                            <td class="px-1 py-2">
                                <a href="admin_edit_handler.php?action=delete_admin&id=<?php echo $a['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>"
                                    class="text-red-500 hover:underline ml-2"
                                    onclick="return confirm('ยืนยันการลบแอดมิน?')">ลบ</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
    </tbody>
    </table>
</div>