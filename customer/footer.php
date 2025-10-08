<?php
$tab_id = $_GET['tab_id'] ?? md5($_SERVER['HTTP_USER_AGENT'] . microtime());
?>
<footer class="bg-[#FB6F92] text-white p-6 mt-6">
    <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <h3 class="text-xl font-bold mb-4">เกี่ยวกับเรา</h3>
            <ul class="space-y-2">
                <li><a href="products.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">สินค้าทั้งหมด</a></li>
                <li><a href="cart.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">คำสั่งซื้อทั้งหมด</a></li>
                <li><a href="contact.php?tab_id=<?php echo urlencode($tab_id); ?>" class="hover:underline">ติดต่อเรา</a></li>
            </ul>
        </div>
        <div>
            <h3 class="text-xl font-bold mb-4">ติดต่อเรา</h3>
            <p>วาลิด เหงบารู</p>
            <p>เบอร์โทร: 08000000</p>
            <p>อีเมล: <a href="mailto:6310110661@psu.ac.th" class="hover:underline">6310110661@psu.ac.th</a></p>
            <p><a href="mailto:Walid.h.hengbaru@gmail.com" class="hover:underline">Walid.h.hengbaru@gmail.com</a></p>
        </div>
        <div>
            <h3 class="text-xl font-bold mb-4">โซเชียลลิงก์</h3>
            <ul class="space-y-2">
                <li><a href="https://facebook.com" class="hover:underline">Facebook</a></li>
                <li><a href="https://twitter.com" class="hover:underline">Twitter</a></li>
                <li><a href="https://instagram.com" class="hover:underline">Instagram</a></li>
            </ul>
        </div>
    </div>
</footer>