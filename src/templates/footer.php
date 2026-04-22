<!DOCTYPE html>
<?php
// src/templates/footer.php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminPage = strpos($scriptName, '/src/admin/') !== false;
?>
</div><!-- end .wrap -->

<?php if ($isAdminPage): ?>
</body>
</html>
<?php return; ?>
<?php endif; ?>

<footer class="footer">
  <div class="footerin">
    <div>© <?= date('Y') ?> Peminjaman Ruangan</div>
    <div class="contact">
      <div class="pill">☎ <span>+62 857-6941-0695</span></div>
      <div class="pill">✉ <span>info@unsri.ac.id</span></div>
    </div>
  </div>
</footer>

<script>
  const burgerBtn = document.getElementById('burgerBtn');
  const mobileMenu = document.getElementById('mobileMenu');

  function closeMenu(){
    if (!mobileMenu || !burgerBtn) return;
    mobileMenu.classList.remove('show');
    burgerBtn.setAttribute('aria-expanded', 'false');
  }
  function toggleMenu(){
    if (!mobileMenu || !burgerBtn) return;
    const isOpen = mobileMenu.classList.toggle('show');
    burgerBtn.setAttribute('aria-expanded', String(isOpen));
  }

  if (burgerBtn) {
    burgerBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleMenu();
    });

    document.addEventListener('click', () => closeMenu());
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
    if (mobileMenu) mobileMenu.addEventListener('click', (e) => e.stopPropagation());
  }
</script>

</body>
</html>