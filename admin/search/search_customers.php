<form method="GET" class="mb-4 flex gap-2">
    <input type="hidden" name="section" value="customers">
    <input type="hidden" name="tab_id" value="<?php echo htmlspecialchars($tab_id); ?>">
    <div class="flex flex-col w-full bg-gray-100/60 border !border-gray-200 rounded ">
        <div class="bg-blue-500 py-2 text-center text-white font-semibold text-[16px] rounded-t shadow">
            ค้นหาลูกค้า
        </div>
        <div class="flex items-center gap-x-5 w-full mt-3">
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-end">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">ชื่อ :</span>
                <input 
                    type="text" 
                    name="name" 
                    id="search_name" 
                    value="<?php echo htmlspecialchars($search_name ?? ''); ?>" 
                    class="!text-[14px] focus:outline-none focus:ring-1 focus:ring-blue-500/60 focus:border-blue-500/60 transition-colors duration-200 cursor-pointer py-[0.3rem] px-1 border !border-gray-200 rounded text-left w-[240px]">
            </div>
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-start">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">สกุล :</span>
                <input 
                    type="text" 
                    name="surname" 
                    id="search_surname" 
                    value="<?php echo htmlspecialchars($search_surname ?? ''); ?>" 
                    class="!text-[14px] focus:outline-none focus:ring-1 focus:ring-blue-500/60 focus:border-blue-500/60 transition-colors duration-200 cursor-pointer py-[0.3rem] px-1 border !border-gray-200 rounded text-left w-[240px]">
            </div>
        </div>
        <div class="flex items-center gap-x-5 w-full mt-3">
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-end">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">ชื่อผู้ใช้ :</span>
                <input 
                    type="text" 
                    name="username" 
                    id="search_username" 
                    value="<?php echo htmlspecialchars($search_username ?? ''); ?>" 
                    class="!text-[14px] focus:outline-none focus:ring-1 focus:ring-blue-500/60 focus:border-blue-500/60 transition-colors duration-200 cursor-pointer py-[0.3rem] px-1 border !border-gray-200 rounded text-left w-[240px]">
            </div>
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-start">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">โทรศัพท์ :</span>
                <input 
                    type="text" 
                    name="telephone" 
                    id="search_telephone" 
                    value="<?php echo htmlspecialchars($search_telephone ?? ''); ?>" 
                    class="!text-[14px] focus:outline-none focus:ring-1 focus:ring-blue-500/60 focus:border-blue-500/60 transition-colors duration-200 cursor-pointer py-[0.3rem] px-1 border !border-gray-200 rounded text-left w-[240px]">
            </div>
        </div>
        <div class="flex items-center gap-x-5 w-full mt-3">
            <div class="flex items-center gap-x-1 py-1 w-1/2 justify-end">
                <span class="w-[140px] !font-semibold text-[14px] text-gray-600 text-right">ที่อยู่ :</span>
                <input 
                    type="text" 
                    name="address" 
                    id="search_address" 
                    value="<?php echo htmlspecialchars($search_address ?? ''); ?>" 
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
                href="admin_index.php?section=customers&tab_id=<?php echo urlencode($tab_id); ?>"
                class="bg-blue-500 rounded text-white w-[120px] py-2 font-semibold text-[14px] text-center hover:cursor-pointer hover:bg-blue-600 hover:shadow"
                >ค้นหาทั้งหมด
            </a>
        </div>
    </div>
</form>
<div class="flex justify-between text-[14px] text-green-700 font-bold mt-4 mb-2">
    <div>พบลูกค้า <span class="text-red-600"><?php echo count($customers); ?></span> รายการ</div>
</div>
<div class="w-full mt-2 mx-auto overflow-x-auto">
    <table class="w-full border border-separate border-spacing-0 border-gray-300 rounded-t text-center">
        <thead>
            <tr>
                <td colspan="6" class="bg-blue-500 py-2 text-center text-white text-[16px] rounded-t">
                    รายชื่อลูกค้า
                </td>
            </tr>
            <tr class="w-full bg-blue-600 grid grid-cols-[90px_90px_90px_200px_120px_1fr_90px_90px] text-white text-center">
                <td class="px-1 py-2 border-r border-gray-300">ชื่อ</td>
                <td class="px-1 py-2 border-r border-gray-300">นามสกุล</td>
                <td class="px-1 py-2 border-r border-gray-300">ชื่อผู้ใช้</td>
                <td class="px-1 py-2 border-r border-gray-300">อีเมล</td>
                <td class="px-1 py-2 border-r border-gray-300">โทรศัพท์</td>
                <td class="px-1 py-2 border-r border-gray-300">ที่อยู่</td>
                <td class="px-1 py-2 border-r border-gray-300">สถานะ</td>
                <td class="px-1 py-2">สถานะ</td>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="6" class="px-1 py-2 text-center text-gray-500">ไม่มีข้อมูลลูกค้า</td></tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                    <tr class="w-full grid grid-cols-[90px_90px_90px_200px_120px_1fr_90px_90px] text-center">
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($c['name'] ?? 'N/A'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($c['surname'] ?? 'N/A'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($c['username'] ?? 'N/A'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($c['email'] ?? 'N/A'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300"><?php echo htmlspecialchars($c['telephone'] ?? 'N/A'); ?></td>
                        <td class="px-1 py-2 border-r border-gray-300">
                            <?php
                                if (!empty($c['addresses'])) {
                                    // เอาที่อยู่หลักก่อน ถ้าไม่มีเอาที่อยู่ล่าสุด
                                    $primary_address = null;
                                        foreach ($c['addresses'] as $addr) {
                                            if ($addr['is_primary']) {
                                                $primary_address = $addr;
                                                break;
                                            }
                                        }
                                        if (!$primary_address) {
                                            $primary_address = $c['addresses'][0];
                                        }
                                        echo htmlspecialchars($primary_address['address']);
                                } else {
                                echo 'N/A';
                                }   
                            ?>
                        </td>
                        <td class="px-1 py-2 border-r border-gray-300 relative text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    class="sr-only peer"
                                    onchange="toggleCustomerStatus(<?php echo $c['id']; ?>)" 
                                    <?php echo $c['active'] ? 'checked' : ''; ?>
                                >
                                <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-green-500 
                                    peer-focus:ring-2 peer-focus:ring-green-300 
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px] 
                                    after:bg-white after:border-gray-300 after:border after:rounded-full 
                                    after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </td>
                        <td class="border p-2">
                            <a href="admin_edit_handler.php?action=delete_customer&user_id=<?php echo $c['id']; ?>&tab_id=<?php echo urlencode($tab_id); ?>" class="text-red-500 hover:underline" onclick="return confirm('แน่ใจหรือไม่ว่าต้องการลบลูกค้านี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
        function toggleCustomerStatus(userId) {
            fetch('admin_edit_handler.php?action=toggle_customer_status&user_id=' + userId + '&tab_id=' + new URLSearchParams(window.location.search).get('tab_id'), {
                method: 'GET'
            }).then(() => {
                location.reload();
            });
        }
    </script>